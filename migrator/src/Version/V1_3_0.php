<?php

    namespace ZubZet\Tooling\Version;

    use ZubZet\Tooling\Modifiers\ComposerModifier;
    use ZubZet\Tooling\Modifiers\SettingsIni;
    use ZubZet\Tooling\Modifiers\ViewMigration;
    use ZubZet\Tooling\ReleaseState;
    use ZubZet\Tooling\Version\BaseVersion;

    class V1_3_0 extends BaseVersion implements VersionInterface {

        public string $stability = ReleaseState::Alpha;

        public function upgrade(): bool {
            // ZubZet 1.3.0 replaces the return-type view renderer with the Katana
            // Blade engine. Convert every legacy view and layout to .blade.php.
            $views = new ViewMigration($this, "blade-view-migration");
            $views->from(["./app/Views"]);
            $views->migrate();

            // Add the health endpoint toggle to ini settings
            $healthEndpoint = new SettingsIni($this, "health-endpoint-settings");
            $healthEndpoint->addProperty("health_endpoint_enabled", "true");
            $healthEndpoint->save();

            // Upgrade composer dependencies (pulls in the Katana-backed framework).
            $composer = new ComposerModifier($this, "composer");
            $composer->upgradeToCurrentVersion();

            return true;
        }
    }

?>
