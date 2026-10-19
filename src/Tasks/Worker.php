<?php

    namespace ZubZet\Framework\Tasks;

    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * @internal
     *
     * One worker process: claim a task, run it, repeat. Several of these run
     * side by side, coordinated only through the claim in the database, so a
     * worker needs to know nothing about its siblings.
     */
    final class Worker {

        /** How often abandoned reservations are looked for, in seconds. */
        private const RECOVERY_INTERVAL_SECONDS = 60;

        /** Longest pause after repeated queue errors, in seconds. */
        private const ERROR_BACKOFF_CAP_SECONDS = 30;

        private string $id;
        private int $startedAt;
        private int $handled = 0;
        private int $lastRecoveryAt = 0;
        private int $consecutiveErrors = 0;
        private bool $stopping = false;

        public function __construct(
            private string $queue = "default",
            private int $sleepSeconds = 1,
            private int $maxJobs = 0,
            private int $timeLimit = 0,
            private int $memoryLimitMb = 0
        ) {
            $this->id = gethostname() . ":" . getmypid();
            $this->startedAt = time();
            $this->lastRecoveryAt = time();
        }

        /**
         * Runs until a stop condition is met.
         *
         * @param bool $once Handle at most one task, then return.
         * @return int Number of tasks handled.
         */
        public function run(OutputInterface $out, bool $once = false): int {
            $runner = new Runner();

            Signals::onShutdown(function() {
                $this->stopping = true;
            });

            while(true) {
                if($this->shouldStop($out)) break;

                $this->recoverPeriodically($runner);

                $claim = $this->claim($out, $once);

                // An error while claiming: back off and try again. A worker
                // outliving a failover or an unmigrated schema matters more
                // than reacting to one failed poll.
                if(false === $claim) continue;

                if(is_null($claim)) {
                    if($once) break;
                    $this->idle();
                    continue;
                }

                $this->handle($out, $runner, $claim);

                if($once) break;
            }

            return $this->handled;
        }

        /**
         * @return array|null|false The claim, null when the queue is empty, or
         *     false when the queue could not be reached.
         */
        private function claim(OutputInterface $out, bool $once) {
            try {
                $claim = zubzet()->getModel("z_task")->claim($this->queue, $this->id);
                $this->consecutiveErrors = 0;

                return $claim;
            } catch(\Throwable $e) {
                if($once) throw $e;

                $this->consecutiveErrors++;
                $backoff = min(self::ERROR_BACKOFF_CAP_SECONDS, $this->consecutiveErrors);

                // Only the first few are reported: a database that stays down
                // must not fill the container log at poll rate.
                if($this->consecutiveErrors <= 3) {
                    $out->writeln("<error>[{$this->id}] cannot reach the queue: {$e->getMessage()}</error>");
                }

                sleep(max(1, $backoff));
                return false;
            }
        }

        /**
         * Runs one claimed task. Nothing a task or its bookkeeping does may end
         * the worker: the supervisor would replace it anyway, and the claim
         * would sit until its reservation expired.
         */
        private function handle(OutputInterface $out, Runner $runner, array $claim): void {
            $startedAt = microtime(true);

            try {
                $record = $runner->run($claim);
            } catch(\Throwable $e) {
                $this->handled++;
                $out->writeln("<error>[{$this->id}] task {$claim["taskId"]} could not be processed: {$e->getMessage()}</error>");
                return;
            }

            $duration = round((microtime(true) - $startedAt) * 1000);
            $this->handled++;

            if(is_null($record)) {
                $out->writeln("<comment>[{$this->id}] dropped orphaned queue entry {$claim["id"]}</comment>");
                return;
            }

            if($record->hasFailed()) {
                $out->writeln("<error>[{$this->id}] {$record->type}#{$record->id} failed after {$duration}ms</error>");
                return;
            }

            if(!$record->isFinished()) {
                $out->writeln("<comment>[{$this->id}] {$record->type}#{$record->id} will retry (attempt {$record->attempts})</comment>");
                return;
            }

            $out->writeln("[{$this->id}] <info>{$record->type}#{$record->id}</info> done in {$duration}ms");
        }

        /**
         * Looks for reservations of workers that died, on a schedule rather
         * than only while idle: a queue that never runs empty would otherwise
         * never recover anything.
         */
        private function recoverPeriodically(Runner $runner): void {
            if((time() - $this->lastRecoveryAt) < self::RECOVERY_INTERVAL_SECONDS) return;

            $this->lastRecoveryAt = time();

            try {
                $runner->recoverAbandoned();
            } catch(\Throwable $e) {}
        }

        /** Waits before polling again. Never busy-polls the database. */
        private function idle(): void {
            sleep(max(1, $this->sleepSeconds));
        }

        /**
         * Recycling conditions, checked between tasks so a running task is
         * never interrupted. The supervisor starts a replacement immediately,
         * which is how a long lived worker sheds leaked memory.
         */
        private function shouldStop(OutputInterface $out): bool {
            Signals::dispatch();

            if($this->stopping) {
                $out->writeln("<comment>[{$this->id}] asked to stop</comment>");
                return true;
            }

            if($this->supervisorIsGone()) {
                $out->writeln("<comment>[{$this->id}] supervisor gone, stopping</comment>");
                return true;
            }

            if($this->maxJobs > 0 && $this->handled >= $this->maxJobs) {
                $out->writeln("<comment>[{$this->id}] handled {$this->handled} tasks, stopping</comment>");
                return true;
            }

            if($this->timeLimit > 0 && (time() - $this->startedAt) >= $this->timeLimit) {
                $out->writeln("<comment>[{$this->id}] reached its time limit, stopping</comment>");
                return true;
            }

            if($this->memoryLimitMb > 0 && (memory_get_usage(true) / 1048576) >= $this->memoryLimitMb) {
                $out->writeln("<comment>[{$this->id}] reached its memory limit, stopping</comment>");
                return true;
            }

            return false;
        }

        /**
         * True once the supervisor that spawned this worker has closed the
         * liveness pipe, which it does when it is stopping and when it dies.
         *
         * The supervisor holds the write end of this process's standard input
         * and never writes to it, so the pipe reports end of file exactly then.
         * That works without any extension, which matters because ext-pcntl is
         * not part of every PHP build.
         *
         * A worker started by hand has a terminal or /dev/null on standard
         * input and is unaffected either way.
         */
        private function supervisorIsGone(): bool {
            if(!WorkerPool::isSupervised()) return false;
            if(!is_resource(STDIN)) return false;

            stream_set_blocking(STDIN, false);
            fgets(STDIN);

            return feof(STDIN);
        }
    }

?>
