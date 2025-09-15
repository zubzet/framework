<?php

    namespace ZubZet\Tooling\Interactions;

    trait CommandInteraction {
        public function runCommand(string $command): bool {
            $this->out->writeln("");
            $this->out->writeln("You can run the following command to fix this issue:");
            $this->out->writeln("<info>{$command}</info>");

            if($this->confirmAutomatedChange()) {
                $this->out->writeln("Executing command <comment>{$command}</comment> ...");
                exec($command . ' 2>&1', $output, $exitCode);

                // Successful execution
                if(0 === $exitCode) return true;

                $this->out->writeln("<error>Command exited with code $exitCode, please check the output:</error>");
                foreach($output as $line) {
                    $this->out->writeln("\t$line");
                }
            }

            return false;
        }
    }

?>