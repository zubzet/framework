## Creating a command
The current implementation simply uses the already existing [controller structure](../core-features/controllers-and-actions.md). Nothing extra needed to do.

## Running a command
To run a command, simply use: `php index.php run <controller> <action> <param1> ...` 

Be sure to use `chdir(realpath(__DIR__))` in your index.php if you are running commands from a different working directory, e.g. as a cronjob.

## Dedicated console commands

Since version 1.4.0, real Symfony console commands can live in `app/Commands/` (in the
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
application wins over modules, and modules win over the framework.