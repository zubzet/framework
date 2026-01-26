<?php

    namespace ZubZet\Framework\Console;

    use Symfony\Component\Console\Application as ConsoleApplication;
    use ZubZet\Framework\Database\Migration\Commands\Import;
    use ZubZet\Framework\Database\Migration\Commands\Seed;
    use ZubZet\Framework\Database\Migration\Commands\Status;
    use ZubZet\Framework\Database\Migration\Commands\Sync;

    class Application {
        public static function bootstrap(\z_framework $booter): ConsoleApplication {
            // TODO: Automatically load commands from a commands directory
            $automaticallyLoadedCommands =  [];

            $console = new ConsoleApplication("ZubZet CLI");
            $console->addCommands(array_merge(
                $automaticallyLoadedCommands,
                [
                    new RunCommand(),
                    new Import(),
                    new Status(),
                    new Sync(),
                    new Seed(),
                ],
            ));
            return $console;
        }
    }

?>