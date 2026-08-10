<?php

    namespace ZubZet\Framework\Registry\Commands;

    use ZubZet\Framework\Registry\Modules;
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    /**
     * Proof of concept: copies MISSING default settings from each installed
     * module's z_config/z_settings.ini into the app's settings file. Existing
     * keys are never touched, existing lines are never rewritten; the merge is
     * append-only and idempotent. Run manually, never at boot.
     */
    final class ModuleSetup extends Command {

        protected function configure(): void {
            $this->setName("module:setup");
            $this->setDescription("Merge missing default settings from installed modules into the app settings file.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $appIniFile = config("config_file");
            $existing = $this->parseIniFile($appIniFile);

            foreach(Modules::roots() as $package => $root) {
                $moduleIniFile = "$root/z_config/z_settings.ini";
                if(!is_file($moduleIniFile)) continue;

                $missing = array_diff_key($this->parseIniFile($moduleIniFile), $existing);
                if(empty($missing)) {
                    $out->writeln("<info>{$package}: nothing to merge</info>");
                    continue;
                }

                $block = "\n; Defaults added by module:setup from {$package}\n";
                foreach($missing as $key => $value) {
                    $error = $this->validate((string) $key, (string) $value);
                    if(!is_null($error)) {
                        $out->writeln("<error>{$package}: refusing key '{$key}': {$error}</error>");
                        return 1;
                    }
                    // Quoted so spaces and special characters round-trip;
                    // validate() already rejected embedded quotes.
                    $block .= "$key = \"$value\"\n";
                    $out->writeln("<info>{$package}: added {$key}</info>");
                }

                if(false === file_put_contents($appIniFile, $block, FILE_APPEND)) {
                    $out->writeln("<error>{$package}: could not write {$appIniFile}</error>");
                    return 1;
                }
                $existing += $missing;

                // Round trip: the file must now parse back to exactly the old
                // keys plus the merged ones; anything else means the append
                // corrupted the file (or a value was mangled by ini parsing).
                if($this->parseIniFile($appIniFile) != $existing) {
                    $out->writeln("<error>{$package}: settings file did not round-trip after the merge, please inspect {$appIniFile}</error>");
                    return 1;
                }

                // Seam: once modules can declare their own setup logic (e.g. a
                // class named in their composer.json extra), invoke it here for
                // $package after its settings merge.
            }

            return Command::SUCCESS;
        }

        /**
         * Reads a flat settings ini with inline ;/# treated as content, using
         * the same workaround as the bootstrap Configuration loader.
         */
        private function parseIniFile(string $file): array {
            if(!is_file($file)) return [];

            $ini_data = file_get_contents($file);
            $ini_data = str_replace(";", "-----semicolon-----", $ini_data);
            $ini_data = str_replace("#", "-----hashtag-----", $ini_data);

            $config = parse_ini_string($ini_data) ?: [];
            foreach($config as $key => $value) {
                $value = str_replace("-----semicolon-----", ";", $value);
                $value = str_replace("-----hashtag-----", "#", $value);
                $config[$key] = $value;
            }
            return $config;
        }

        /**
         * Settings a module must never introduce: framework behavior switches
         * and infrastructure credentials stay under the operator's control.
         */
        private const RESERVED_KEYS = [
            "allow_env_config", "showErrors", "maintenance_mode",
            "health_endpoint_enabled", "uploadFolder", "rootDirectory",
            "host", "defaultIndex", "execution_type",
            "modules", "modules_disabled",
        ];
        private const RESERVED_PREFIXES = ["db", "logger_", "mail_", "z_", "config_"];

        /** Returns an error description, or null when the pair is safe to append. */
        private function validate(string $key, string $value): ?string {
            if(!preg_match('/^[A-Za-z0-9_.]+$/', $key)) {
                return "key contains characters outside [A-Za-z0-9_.]";
            }
            if(in_array($key, self::RESERVED_KEYS, true)) {
                return "key is a framework-reserved setting";
            }
            foreach(self::RESERVED_PREFIXES as $prefix) {
                if(str_starts_with($key, $prefix)) {
                    return "key starts with the framework-reserved prefix '$prefix'";
                }
            }
            foreach(["\r", "\n", "\""] as $forbidden) {
                if(str_contains($value, $forbidden)) {
                    return "value contains a line break or quote";
                }
            }
            if(str_contains($value, "-----semicolon-----") || str_contains($value, "-----hashtag-----")) {
                return "value contains a reserved placeholder literal";
            }
            return null;
        }
    }

?>
