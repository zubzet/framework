<?php

    namespace ZubZet\Framework\Console;

    use Symfony\Component\Console\Application as ConsoleApplication;

    class Application {
        public static function bootstrap(\z_framework $booter): ConsoleApplication {
            $runCommand = new RunCommand($booter);

            // TODO: Automatically load commands from a commands directory

            $console = new ConsoleApplication("ZubZet CLI");
            $console->addCommands([$runCommand]);
            return $console;
        }
    }

?>