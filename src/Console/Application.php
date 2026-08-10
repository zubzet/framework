<?php

    namespace ZubZet\Framework\Console;

    use ZubZet\Framework\Support\Commands\Startup;
    use ZubZet\Framework\Registry\Commands\ModuleSetup;
    use ZubZet\Framework\Database\Migration\Commands\Seed;
    use ZubZet\Framework\Database\Migration\Commands\Sync;
    use ZubZet\Framework\Database\Migration\Commands\Status;
    use ZubZet\Framework\Database\Migration\Commands\Migrate;
    use ZubZet\Framework\Authentication\Commands\HashingAlgorithmMigration;
    use Symfony\Component\Console\Application as ConsoleApplication;
    use ZubZet\Framework\Database\Migration\Commands\UnlockMigration;
    use ZubZet\Framework\Testing\Coverage\Commands\Stop as CoverageStop;
    use ZubZet\Framework\Testing\Coverage\Commands\Start as CoverageStart;

    class Application {
        public static function bootstrap(\z_framework $booter): ConsoleApplication {
            // Convention commands from userspace and modules, in precedence order.
            $automaticallyLoadedCommands = CommandDiscovery::commands();

            $console = new ConsoleApplication("ZubZet CLI");

            // Symfony resolves command-name collisions last-add-wins, so the
            // framework registers first and the discovered commands follow in
            // REVERSE precedence order: the userspace command lands last and
            // overrides any module or framework command sharing its name.
            $console->addCommands(array_merge(
                [
                    new RunCommand(),
                    new Migrate(),
                    new Status(),
                    new Sync(),
                    new Seed(),
                    new UnlockMigration(),
                    new HashingAlgorithmMigration(),
                    new Startup(),
                    new ModuleSetup(),
                    new CoverageStart(),
                    new CoverageStop(),
                ],
                array_reverse($automaticallyLoadedCommands),
            ));
            return $console;
        }
    }

?>