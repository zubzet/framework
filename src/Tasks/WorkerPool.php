<?php

    namespace ZubZet\Framework\Tasks;

    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * @internal
     *
     * Supervises N worker processes.
     *
     * Workers are separate processes rather than forks: each boots its own
     * framework instance with its own database connection, which is what makes
     * running four of them safe (a forked process would inherit and corrupt the
     * parent's connection socket). proc_open is part of PHP itself, so the pool
     * needs no extension.
     *
     * Each child's standard input is a pipe this supervisor holds open and
     * never writes to. It is a liveness channel: closing it, whether
     * deliberately on shutdown or by the supervisor dying, makes every child
     * finish its current task and exit.
     */
    final class WorkerPool {

        /** Environment marker telling a child it was started by a supervisor. */
        private const SUPERVISED_ENV = "ZUBZET_TASK_SUPERVISED";

        /** How long to wait between liveness checks of the children. */
        private const SUPERVISION_INTERVAL_SECONDS = 1;

        /** How long to wait for children to finish their task on shutdown. */
        private const DRAIN_TIMEOUT_SECONDS = 60;

        /** @var array<int, array{process: resource, pipes: array}> */
        private array $children = [];

        private bool $stopping = false;

        public function __construct(
            private int $workers,
            private array $childArguments
        ) {}

        /** True when the current process is a worker started by a supervisor. */
        public static function isSupervised(): bool {
            return "1" === getenv(self::SUPERVISED_ENV);
        }

        /**
         * Keeps the pool at strength until the process is asked to stop, then
         * drains it.
         */
        public function run(OutputInterface $out): int {
            $entryPoint = $this->entryPoint();
            if(is_null($entryPoint)) {
                $out->writeln("<error>Could not determine the console entry point to start workers with.</error>");
                return 1;
            }

            Signals::onShutdown(function() {
                $this->stopping = true;
            });

            $out->writeln("Starting <info>{$this->workers}</info> workers.");

            while(!$this->stopping) {
                // Every slot that is empty or holds an exited child is filled,
                // so a worker that recycled, crashed, or failed to start at all
                // is replaced on the next pass rather than lost.
                for($index = 1; $index <= $this->workers; $index++) {
                    if($this->isRunning($index)) continue;

                    $this->reap($index);
                    $this->start($out, $index, $entryPoint);
                }

                sleep(self::SUPERVISION_INTERVAL_SECONDS);
                Signals::dispatch();
            }

            return $this->drain($out);
        }

        /**
         * Closes every child's liveness pipe and waits for them to finish the
         * task they are running.
         *
         * Without a signal extension this never runs: the process is killed
         * outright and the tasks in flight are recovered through their
         * reservation timeout instead.
         */
        private function drain(OutputInterface $out): int {
            $out->writeln("Stopping. Letting workers finish their current task.");

            foreach($this->children as $index => $child) {
                $this->closePipes($index);
            }

            $deadline = time() + self::DRAIN_TIMEOUT_SECONDS;
            while(time() < $deadline && $this->anyRunning()) {
                sleep(1);
            }

            $remaining = $this->runningCount();
            if($remaining > 0) {
                $out->writeln("<comment>{$remaining} workers were still busy and are being left to their reservation timeout.</comment>");
                return 0;
            }

            $out->writeln("All workers stopped.");
            return 0;
        }

        /** Spawns one child worker with its liveness pipe attached. */
        private function start(OutputInterface $out, int $index, string $entryPoint): void {
            $command = array_merge([PHP_BINARY, $entryPoint, "queue:work"], $this->childArguments);

            $descriptors = [
                0 => ["pipe", "r"],
                1 => ["file", "php://stdout", "a"],
                2 => ["file", "php://stderr", "a"],
            ];

            $pipes = [];
            $process = proc_open(
                $command,
                $descriptors,
                $pipes,
                null,
                // getenv() rather than $_ENV: proc_open replaces the child's
                // environment instead of extending it, and $_ENV is only
                // populated when variables_order contains "E", which the
                // php.ini-production and php.ini-development templates do not.
                // With $_ENV the children would start with nothing but this
                // marker, losing PATH and every CONFIG_* value.
                array_merge(getenv(), [self::SUPERVISED_ENV => "1"]),
            );

            // The slot stays empty and the next supervision pass tries again,
            // rather than the pool silently running below strength.
            if(!is_resource($process)) {
                $out->writeln("<error>Failed to start worker {$index}, retrying.</error>");
                return;
            }

            $this->children[$index] = ["process" => $process, "pipes" => $pipes];
        }

        private function isRunning(int $index): bool {
            $child = $this->children[$index] ?? null;
            if(is_null($child)) return false;

            $status = proc_get_status($child["process"]);
            return (bool) $status["running"];
        }

        private function anyRunning(): bool {
            return $this->runningCount() > 0;
        }

        private function runningCount(): int {
            $running = 0;
            foreach(array_keys($this->children) as $index) {
                if($this->isRunning($index)) $running++;
            }

            return $running;
        }

        /** Closes the handles of a child that has exited, so nothing leaks. */
        private function reap(int $index): void {
            $child = $this->children[$index] ?? null;
            if(is_null($child)) return;

            $this->closePipes($index);
            proc_close($child["process"]);
            unset($this->children[$index]);
        }

        private function closePipes(int $index): void {
            foreach($this->children[$index]["pipes"] ?? [] as $key => $pipe) {
                if(!is_resource($pipe)) continue;

                fclose($pipe);
                $this->children[$index]["pipes"][$key] = null;
            }
        }

        /**
         * The script this process was started from, which is the application's
         * index.php. Children are started through the same entry point so they
         * boot with the application's configuration, not the framework's.
         */
        private function entryPoint(): ?string {
            $candidates = [
                $_SERVER["SCRIPT_FILENAME"] ?? null,
                $_SERVER["PHP_SELF"] ?? null,
                $_SERVER["argv"][0] ?? null,
            ];

            foreach($candidates as $candidate) {
                if(empty($candidate)) continue;

                $path = realpath($candidate);
                if(false !== $path && is_file($path)) return $path;
            }

            return null;
        }
    }

?>
