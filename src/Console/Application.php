<?php

    namespace ZubZet\Framework\Console;

    use Symfony\Component\Console\Application as ConsoleApplication;
    use ZubZet\Framework\Database\Migration\Commands\Migrate;
    use ZubZet\Framework\Database\Migration\Commands\Seed;
    use ZubZet\Framework\Database\Migration\Commands\Status;
    use ZubZet\Framework\Database\Migration\Commands\Sync;
    use ZubZet\Framework\Database\Migration\Commands\UnlockMigration;
    use ZubZet\Framework\Support\Commands\Startup;
    use ZubZet\Framework\Testing\Coverage\Commands\Stop as CoverageStop;
    use ZubZet\Framework\Testing\Coverage\Commands\Start as CoverageStart;

    class Application {
        public static function bootstrap(\z_framework $booter): ConsoleApplication {
            // TODO: Automatically load commands from a commands directory
            $automaticallyLoadedCommands =  [];

            $console = new ConsoleApplication("ZubZet CLI");
            $console->addCommands(array_merge(
                $automaticallyLoadedCommands,
                [
                    new RunCommand(),
                    new Migrate(),
                    new Status(),
                    new Sync(),
                    new Seed(),
                    new UnlockMigration(),
                    new Startup(),
                    new CoverageStart(),
                    new CoverageStop(),
                ],
            ));
            return $console;
        }
    }

?>