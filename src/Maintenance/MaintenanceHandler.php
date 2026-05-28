<?php

    namespace ZubZet\Framework\Maintenance;

    /**
     * Standalone maintenance gate. Designed to run immediately after
     * Configuration so an admin can flip the switch and perform live
     * operations (migrations, deploys) without users reaching the app.
     *
     * Depends only on the loaded configuration: no logger, no exception
     * handler, no database. Failing to resolve the mode falls through to
     * DISABLED so the gate can never lock the site by itself.
     *
     * @internal
     */
    class MaintenanceHandler {

        public static $COOKIE_KEY = 'maintenance';

        private static string $mode = MaintenanceMode::DISABLED;

        public static function gate(): void {
            // Parse the config
            $mode = strtolower(config('maintenance_mode', default: MaintenanceMode::DISABLED));
            self::$mode = MaintenanceMode::isValid($mode) ? $mode : MaintenanceMode::DISABLED;

            // CLI requests bypass every mode except FULL.
            if(isCli() && self::$mode !== MaintenanceMode::FULL) return;

            // Handle bypasses according to the mode
            $hasBypass = match(self::$mode) {
                MaintenanceMode::DISABLED => true,
                MaintenanceMode::SOFT => self::checkBypassCookie(),
                MaintenanceMode::ENABLED => false,
                MaintenanceMode::FULL => false,
            };
            if($hasBypass) return;

            // Discover the template. CLI hits skip the lookup so cron jobs
            // see a minimal, unambiguous string instead of HTML; HTTP hits
            // still get the styled page even in FULL mode.
            $page = "Service Unavailable";
            if(!isCli()) {
                foreach(["app/Views", __DIR__] as $possiblePath) {
                    $template = "$possiblePath/maintenance.html";
                    if(!is_file($template)) continue;

                    $contents = @file_get_contents($template);
                    if(empty($contents) || str_contains($contents, "<?php")) continue;

                    $page = $contents;
                    break;
                }
            }

            // CLI: stderr + non-zero exit so cron noticed the block.
            if(isCli()) {
                fwrite(STDERR, $page . "\n");
                exit(1);
            }

            // HTTP: 503 with the page body
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 300');
            echo $page;
            exit;
        }

        public static function isActive(): bool {
            return MaintenanceMode::DISABLED !== self::$mode;
        }

        public static function getMode(): string {
            return self::$mode;
        }

        public static function checkBypassCookie(): bool {
            return array_key_exists(self::$COOKIE_KEY, ($_COOKIE ?? []));
        }

    }
