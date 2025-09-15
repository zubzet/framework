<?php

    namespace ZubZet\Tooling\Modifiers;

    class SettingsIni extends BaseModifier {
        private string $fileLocation = 'z_config/z_settings.ini';
        private array $settings;

        public function configure(): void {
            if(!file_exists($this->fileLocation)) {
                throw new \RuntimeException('Settings file not found: ' . $this->fileLocation);
            }

            $content = file_get_contents($this->fileLocation);
            $this->settings = preg_split('/\R/', $content);
        }

        private function propertyExistsAt(string $name): ?int {
            foreach($this->settings as $i => $setting) {
                if(str_contains($setting, $name)) return $i;
            }
            return null;
        }

        private function formatPropertyAsLine(string $name, string $value): string {
            $line = "$name =";
            if(!empty($value)) $line .= " ";
            return $line . $value;
        }

        private bool $isFirstAddition = true;
        public function addProperty(string $name, string $value, ?string $afterProperty = null): void {
            if(!is_null($this->propertyExistsAt($name))) {
                $this->out->writeln("Property <comment>$name</comment> already exists, skipping addition.");
                return;
            }

            $this->out->writeln("Adding property <info>$name</info> with value '<info>$value</info>' to settings.");

            // Place the new property at a specific place if requested
            if(!is_null($afterProperty)) {
                $afterPropertyAt = $this->propertyExistsAt($afterProperty);

                // Only add after the requested property, otherwise simply add it to the end
                if(!is_null($afterPropertyAt)) {
                    array_splice(
                        $this->settings,
                        $afterPropertyAt + 1,
                        0,
                        [$this->formatPropertyAsLine($name, $value)],
                    );
                    return;
                }
            }

            // Add a blank line before the first addition for readability
            if($this->isFirstAddition) {
                $this->settings[] = "";
                $this->isFirstAddition = false;
            }

            // Add the new value at the end
            $this->settings[] = $this->formatPropertyAsLine($name, $value);
        }

        public function removeProperty(
            string $name,
            null|string|array $warning = null,
            null|string|array $warnIf = ["1", "true", "on", "yes"],
        ): void {

            $propertyAt = $this->propertyExistsAt($name);
            if(is_null($propertyAt)) {
                $this->out->writeln("Property <comment>$name</comment> does not exist, skipping removal.");
                return;
            }

            // Allow for a warning if removing a property would likely break something
            if(!empty($warning)) {
                if(!is_array($warnIf)) $warnIf = [$warnIf];
                if(!is_array($warning)) $warning = [$warning];

                $shouldWarn = false;
                $property = strtolower($this->settings[$propertyAt]);
                foreach($warnIf as $warningValue) {
                    if(str_contains($property, strtolower($warningValue))) {
                        $shouldWarn = true;
                        break;
                    }
                }

                if($shouldWarn) {
                    $this->out->writeln("<error>Warning:</error>");

                    foreach($warning as $line) {
                        $this->out->writeln("  $line");
                    }

                    if(!$this->confirmAutomatedChange()) {
                        $this->abortRequiringUserAction();
                        return;
                    }
                }
            }

            $this->out->writeln("Removing property <info>$name</info> from settings.");
            unset($this->settings[$propertyAt]);
            $this->settings = array_values($this->settings);
        }

        public function modifyProperty(string $name, string $value): void {
            $propertyAt = $this->propertyExistsAt($name);

            // If the property doesn't exist, simply create it
            if(is_null($propertyAt)) {
                $this->addProperty($name, $value);
                return;
            }

            $property = &$this->settings[$propertyAt];
            if(str_contains($property, $value)) {
                $this->out->writeln("Property <comment>$name</comment> is already set to <comment>$value</comment>, skipping modification.");
                return;
            }

            // Otherwise modify the value
            $this->out->writeln("Modifying property <info>$name</info> to have value <info>$value</info> in settings.");
            $this->settings[$propertyAt] = "$name = $value";
        }

        private bool $shouldCollapseConsecutiveEmptyRows = false;
        public function collapseConsecutiveEmptyRows(): void {
            $this->shouldCollapseConsecutiveEmptyRows = true;
        }

        private bool $shouldAssertEmptyLastRow = false;
        public function assertEmptyLastRow(): void {
            $this->shouldAssertEmptyLastRow = true;
        }

        public function save(): void {
            // Collapse consecutive empty rows
            if($this->shouldCollapseConsecutiveEmptyRows) {
                $collapsedRows = 0;
                $prevEmpty = false;
                $this->settings = array_values(array_filter(
                    $this->settings,
                    function($line) use (&$prevEmpty, &$collapsedRows) {
                        if(empty($line) && $prevEmpty) {
                            $collapsedRows++;
                            return false;
                        }
                        $prevEmpty = empty($line);
                        return true;
                    },
                ));

                if($collapsedRows > 0) {
                    $this->out->writeln("Styling <info>$collapsedRows consecutive empty rows</info> to be collapsed into one.");
                } else {
                    $this->out->writeln("No <comment>consecutive empty rows</comment> found, Skipping...");
                }
            }

            // Add empty last row
            if($this->shouldAssertEmptyLastRow) {
                $lastRow = $this->settings[count($this->settings) - 1];

                if(!empty($lastRow)) {
                    $this->out->writeln("Styling <info>last empty row</info> to exist.");
                    $this->settings[] = "";
                } else {
                    $this->out->writeln("Last row is already <comment>empty</comment>, Skipping...");
                }
            }

            if($this->upgrade->dry) return;
            file_put_contents(
                $this->fileLocation,
                implode(PHP_EOL, $this->settings),
            );
        }
    }

?>