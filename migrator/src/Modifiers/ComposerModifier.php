<?php

    namespace ZubZet\Tooling\Modifiers;

    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Interactions\CommandInteraction;

    class ComposerModifier extends JsonModifier {

        use CommandInteraction;

        public function configure() {
            $this->optional();
            $this->from("composer.lock");
        }

        public function upgradeToCurrentVersion(?string $desiredVersion = null): void {
            if(is_null($desiredVersion)) {
                $desiredVersion = $this->version->semanticVersion;
            }

            // Find installed version
            $installedVersion = "v0.0.0";
            foreach(($this->data["packages"] ?? []) as $package) {
                if("zubzet/framework" == $package["name"]) {
                    $installedVersion = $package["version"];
                }
            }

            // Remove leading "v" if present
            $installedVersion = ltrim($installedVersion, "v");

            // Remove version suffix (e.g. "-RC1") if present
            $installedVersion = preg_replace('/-.*/', '', $installedVersion);

            // Check if already at desired version or beyond
            if(version_compare($installedVersion, $desiredVersion, ">=")) {
                $this->out->writeln("Already at version <comment>$installedVersion</comment>, satisfying version <comment>$desiredVersion</comment>. Skipping...");
                return;
            }

            // Upgrade to the desired version
            $desiredVersionParts = explode('.', $desiredVersion);

            if(count($desiredVersionParts) < 2) {
                throw new \InvalidArgumentException("Invalid version: $desiredVersion");
            }

            $desiredVersion = "$desiredVersionParts[0].$desiredVersionParts[1]";
            $versionConstraint = "^$desiredVersion";

            // Handle minimum stability for RC versions
            if($this->version->stability !== ReleaseState::Stable) {
                $versionConstraint .= "-".$this->version->stability;
            }

            // Make the upgrade
            $this->runCommand(implode(" ", [
                "composer require",
                "\"zubzet/framework:$versionConstraint\"",
                "--with-all-dependencies",
                "--ignore-platform-reqs",
            ]));
        }
    }
?>