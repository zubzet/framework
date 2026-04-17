<?php
    namespace ZubZet\Framework\Support\Commands;

    use Composer\InstalledVersions;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Console\Formatter\OutputFormatterStyle;

    final class Startup extends Command {

        private OutputInterface $out;

        protected function configure(): void {
            $this->setName("info:startup");
            $this->setDescription("Prints information for the startup process of the framework.");
        }

        protected function execute(InputInterface $_in, OutputInterface $out): int {
            $this->out = $out;
            $out->setDecorated(true);

            $out->getFormatter()->setStyle("brand", new OutputFormatterStyle("magenta", null, ["bold"]));
            $out->getFormatter()->setStyle("version", new OutputFormatterStyle("gray"));
            $out->getFormatter()->setStyle("label", new OutputFormatterStyle("white", null, ["bold"]));
            $out->getFormatter()->setStyle("url", new OutputFormatterStyle("cyan"));
            $out->getFormatter()->setStyle("muted", new OutputFormatterStyle("gray"));
            $out->getFormatter()->setStyle("bar", new OutputFormatterStyle("magenta"));

            $version = $this->getInstalledVersion();
            $pageName = config('pageName', default: 'Unknown. Set pageName in settings!');
            $executionType = config('execution_type', default: 'Unknown. Set execution_type in settings!');
            $assetVersion = config('assetVersion', default: 'Unknown. Set assetVersion in settings!');

            $this->line();
            $this->line("<brand>▲ ZubZet</brand>  <version>{$version}</version>  {$pageName}");
            $this->line();
            $this->line("<bar>┃</bar>  <label>Open:</label>  <url>{$this->getConfiguredHost()}</url>");
            $this->line();
            $this->line("<muted>─────────────────────────────────────────────</muted>");
            $this->infoRow("Environment", $executionType);
            $this->infoRow("PHP Runtime", "v" . PHP_VERSION . "");
            $this->infoRow("Assets", "v{$assetVersion}");
            $this->line("<muted>─────────────────────────────────────────────</muted>");
            $this->line();
            $this->line("<muted>run</muted> <label>'npm run stop'</label> <muted>to stop the server</muted>");
            $this->line();

            return Command::SUCCESS;
        }

        private function line(string $content = ""): void {
            $this->out->writeln("  {$content}");
        }

        private function infoRow(string $label, string $value): void {
            $this->line("<muted>{$label}</muted>\t<label>{$value}</label>");
        }

        private function getInstalledVersion(): string {
            return InstalledVersions::getPrettyVersion('zubzet/framework') ?? 'unknown';
        }

        private function getConfiguredHost(): string {
            return config('host', default: 'Unknown / Check docker-compose-base.yml');
        }

    }
