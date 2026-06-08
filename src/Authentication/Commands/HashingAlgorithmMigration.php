<?php

    namespace ZubZet\Framework\Authentication\Commands;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use ZubZet\Framework\Database\Migration\Commands\Traits\DatabaseConnection;

    /**
     * Wraps every `legacy` password hash in a fresh native hash (the "onion"),
     * protecting dormant accounts at rest against a future breach without needing
     * the plaintext. Idempotent and reusable: it only touches rows still on the
     * `legacy` scheme, so re-running (or running after some users have already
     * logged in and been upgraded) is safe and does no redundant work.
     *
     * Requires the password_scheme migration to have run first.
     */
    final class HashingAlgorithmMigration extends Command {

        use DatabaseConnection;

        protected function configure(): void {
            $this->setName("auth:migrate-hashing");
            $this->setDescription(
                "Migrate legacy password hashes to the current algorithm (onion-wrap) for at-rest protection. Idempotent."
            );
            $this->addOption(
                "dry",
                "d",
                InputOption::VALUE_NONE,
                "Report what would change without writing.",
            );
            $this->setDatabaseConnection();
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $dry = (bool) $in->getOption("dry");

            $rows = model("z_login")->getLegacyPasswords();

            $total = \count($rows);
            if(0 === $total) {
                $out->writeln("<info>No legacy passwords to wrap. Nothing to do.</info>");
                return Command::SUCCESS;
            }

            $out->writeln("<info>Found {$total} legacy password(s)…</info>");
            if($dry) return Command::SUCCESS;

            $done = 0;
            foreach($rows as $row) {
                $done++;
                if($done % 100 === 0 || $done === $total) {
                    $out->writeln("\t{$done} of {$total}");
                }

                model("z_login")->onionWrapPassword((int) $row["id"], $row["password"]);
            }

            $out->writeln("<info>Done: {$done} row(s) wrapped.</info>");
            return Command::SUCCESS;
        }
    }
