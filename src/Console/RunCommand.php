<?php

    namespace ZubZet\Framework\Console;

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    final class RunCommand extends Command {
        protected function configure(): void {
            $this->setName("run");
            $this->setDescription("Execute a controller action from the console");

            $this->addArgument(
                "controller",
                InputArgument::REQUIRED,
                "Controller name (i.e. dashboard)",
            );

            $this->addArgument(
                "action",
                InputArgument::OPTIONAL,
                "Action name without 'action_'",
                "index",
            );

            $this->addArgument(
                "parameters",
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                "Optional Parameters",
            );
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $controller = strtolower((string) $in->getArgument('controller'));
            $action = strtolower((string) $in->getArgument('action'));
            $parameters = $in->getArgument("parameters") ?? [];

            // Load the underlying data
            $actionsByController = ActionDiscovery::find(zubzet()->z_controllers);

            // Validation: Controller
            $validController = array_key_exists($controller, $actionsByController);

            if(!$validController) {
                $out->writeln('<error>Unknown Controller:</error> ' . $controller);
                $out->writeln('Known Controllers: ' . implode(', ', array_keys($actionsByController)));
                return 1;
            }

            // Validation: Action
            $actions = $validController ? $actionsByController[$controller] : [];
            $validAction = in_array($action, $actions, true) || in_array('fallback', $actions, true);

            if(!$validAction) {
                $out->writeln('<error>Unknown Action for ' . $controller . ':</error> ' . $action);
                $out->writeln('Known Actions: ' . implode(', ', $actionsByController[$controller]));
                return 1;
            }

            // Execute the action
            zubzet()->executePath([
                $controller,
                $action,
                ...$parameters,
            ]);

            return 0;
        }
    }

?>