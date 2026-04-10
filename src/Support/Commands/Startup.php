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
            $pageName = config('pageName', default: '');
            $executionType = config('execution_type', default: 'unknown');
            $assetVersion = config('assetVersion', default: 'unknown');
            $phpVersion = PHP_VERSION;

            $this->line();
            $this->line("<brand>▲ ZubZet</brand>  <version>{$version}</version>  {$pageName}");
            $this->line();
            $this->importantRow("Local", $this->getConfiguredHost());
            $this->line();
            $this->line("<muted>─────────────────────────────────────────────</muted>");
            $this->infoRow("env", $executionType);
            $this->infoRow("php", "v{$phpVersion}");
            $this->infoRow("assets", "v{$assetVersion}");
            $this->line("<muted>─────────────────────────────────────────────</muted>");
            $this->line();
            $this->line("<muted>run</muted> <label>'npm run stop'</label> <muted>to stop the server</muted>");
            $this->line();

            return Command::SUCCESS;
        }

        private function line(?string $content = null): void {
            // Append two spaced at the beginning of the line for better readability
            $output = "  {$content}";

            // If there is no content, just print an empty line without spaces
            if(is_null($content)) $output = "";

            $this->out->writeln($output);
        }

        private function importantRow(string $label, string $url): void {
            $this->line("<bar>┃</bar>  <label>{$label}</label>  <url>{$url}</url>");
        }

        private function infoRow(string $label, string $value): void {
            $this->line("<muted>{$label}</muted>  <label>{$value}</label>");
        }

        private function getInstalledVersion(): string {
            return InstalledVersions::getPrettyVersion('zubzet/framework') ?? 'unknown';
        }

        private function getConfiguredHost(): string {
            return rtrim($this->config('host', 'http://localhost'), '/');
        }

        private function config(string $key, string $default = 'unknown'): string {
            try {
                return (string) zubzet()->{$key};
            } catch (\Throwable) {
                return $default;
            }
        }

    }
