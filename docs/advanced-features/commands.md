## Creating a command
The current implementation simply uses the already existing [controller structure](../core-features/controllers-and-actions.md). Nothing extra needed to do.

## Running a command
To run a command, simply use: `php index.php run <controller> <action> <param1> ...` (`action` defaults to `index`, names are case-insensitive). The extra tokens are readable in the action with `$req->getParameters()`. An unknown controller or action lists the known ones and exits with status `1`, otherwise `run` exits with `0` whatever the action returns. A controller with `action_fallback` accepts any action name. Only the top level of `app/Controllers/` and the module controller folders is scanned (every `*.php` there is included, so top-level code runs), and only `action_` methods declared in the class itself count, not inherited ones.

Be sure to use `chdir(realpath(__DIR__))` in your index.php if you are running commands from a different working directory, e.g. as a cronjob.

## Restricting a controller to the console
Controller actions are also reachable over HTTP, so a command controller should refuse web requests. `$req->checkPermission("console")` is a reserved permission name: it passes only when PHP runs in CLI mode, no login or role is involved. Over HTTP the request is answered with the 403 page and cancelled. With `true` as second parameter it returns `false` instead of showing the 403 page.

Calling it from the controller's `__construct` makes every action of that controller CLI-only, because the constructor runs before any action (and before the 404 check) is dispatched:

```php
class ReportController extends z_controller {
    public function __construct(Request $req, Response $res) {
        $req->checkPermission("console");
    }

    public function action_daily(Request $req, Response $res) {
        // Only reachable via: php index.php run report daily
    }
}
```

## Dedicated console commands

Since version 1.3.0, real Symfony console commands can live in `app/Commands/` (in the
application and in [modules](modules.md)). Each file declares one global class named like the
file, extending the Symfony `Command` class:

```php
<?php
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class ReportCommand extends Command {
        protected function configure(): void {
            $this->setName("report:daily");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $out->writeln(model("Report")->summary());
            return Command::SUCCESS;
        }
    }
?>
```

Run it with `php index.php report:daily`; it appears in `php index.php list` next to the
framework commands. The framework is fully booted before `execute()` runs, so `config()`,
`model()`, and the database are available. When two roots register the same command name, the
application wins over modules, and modules win over the framework. The console dispatches as `GET /`, so an explicit route for `GET /` runs instead of any command.