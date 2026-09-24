<?php

    namespace ZubZet\Framework\Tasks\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Tasks\Worker;
    use ZubZet\Framework\Tasks\WorkerPool;

    /**
     * Runs background tasks. This is what the worker container executes.
     *
     * With --workers greater than one the process supervises that many worker
     * processes and restarts them as they recycle; with one it is the worker.
     */
    final class Work extends Command {

        protected function configure(): void {
            $this->setName("queue:work");
            $this->setDescription("Run queued background tasks");

            $this->addOption("workers", "w", InputOption::VALUE_REQUIRED, "How many worker processes to run", 1);
            $this->addOption("queue", null, InputOption::VALUE_REQUIRED, "Which queue to consume", "default");
            $this->addOption("sleep", null, InputOption::VALUE_REQUIRED, "Seconds to wait when no task is available", 1);
            $this->addOption("max-jobs", null, InputOption::VALUE_REQUIRED, "Recycle a worker after this many tasks (0 for never)", 0);
            $this->addOption("time-limit", null, InputOption::VALUE_REQUIRED, "Recycle a worker after this many seconds (0 for never)", 0);
            $this->addOption("memory-limit", null, InputOption::VALUE_REQUIRED, "Recycle a worker above this many MB (0 for never)", 0);
            $this->addOption("once", null, InputOption::VALUE_NONE, "Run at most one task, then exit");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $workers = max(1, (int) $in->getOption("workers"));
            $queue = (string) $in->getOption("queue");
            $once = (bool) $in->getOption("once");

            // One worker is the process itself; a pool would only add a
            // supervisor with nothing to balance.
            if($workers > 1 && !$once) {
                return (new WorkerPool($workers, $this->childArguments($in)))->run($out);
            }

            $out->writeln("Worker started on queue <info>{$queue}</info>.");

            $worker = new Worker(
                $queue,
                max(0, (int) $in->getOption("sleep")),
                max(0, (int) $in->getOption("max-jobs")),
                max(0, (int) $in->getOption("time-limit")),
                max(0, (int) $in->getOption("memory-limit")),
            );

            $handled = $worker->run($out, $once);
            $out->writeln("Worker stopped after <info>{$handled}</info> tasks.");

            return Command::SUCCESS;
        }

        /**
         * The options a supervised child is started with: everything this
         * process received, except the worker count, which becomes one.
         *
         * @return string[]
         */
        private function childArguments(InputInterface $in): array {
            return [
                "--workers=1",
                "--queue=" . $in->getOption("queue"),
                "--sleep=" . (int) $in->getOption("sleep"),
                "--max-jobs=" . (int) $in->getOption("max-jobs"),
                "--time-limit=" . (int) $in->getOption("time-limit"),
                "--memory-limit=" . (int) $in->getOption("memory-limit"),
            ];
        }
    }

?>
