<?php
    namespace ZubZet\Framework\Database\Migration\Commands\Traits;

    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    trait DatabaseConnection {
        private function addDatabaseWaitOption(): void {
            $this->addOption(
                "no-wait",
                null,
                InputOption::VALUE_NONE,
                "Fail at once instead of waiting for the database to accept connections",
            );
        }

        // Runs before execute(): a database that is still starting is waited for instead of failing the command
        protected function initialize(InputInterface $in, OutputInterface $out): void {
            $attempts = $in->getOption("no-wait") ? 1 : configNumeric("db_wait_attempts", 10);
            $delay = configNumeric("db_wait_delay", 3);

            $username = config("dbusername_elevated");
            $password = config("dbpassword_elevated");
            $useElevatedUser = !empty($username) && !empty($password);

            $attempt = 1;
            while(true) {
                try {
                    if($useElevatedUser) {
                        db()->switchUser($username, $password);
                    } else {
                        db()->assertConnection();
                    }
                    return;
                } catch(\mysqli_sql_exception $e) {
                    // Out of attempts: the command fails with the real connection error
                    if($attempt >= $attempts) throw $e;

                    $out->writeln("<comment>Database not reachable ({$e->getMessage()}), attempt $attempt of $attempts, retrying in {$delay}s</comment>");
                    sleep($delay);
                    $attempt++;
                }
            }
        }
    }
