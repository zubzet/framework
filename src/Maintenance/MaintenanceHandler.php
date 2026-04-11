<?php

    namespace ZubZet\Framework\Maintenance;

    class MaintenanceHandler {

        public static $COOKIE_KEY = 'maintenance';

        private static string|bool $mode = MaintenanceMode::DISABLED;

        public function __construct() {
            // Parse maintenance mode from configuration
            self::$mode = MaintenanceMode::parse(config('maintenance_mode', default: "disabled"));

            // Handle maintenance mode for incoming requests
            if($this->hasBypass()) return;

            // If there's no bypass, send maintenance response
            $this->sendMaintenanceResponse();
        }

        // Determines if the current request should bypass maintenance mode
        private function hasBypass(): bool {
            return match(self::$mode) {
                // In disabled mode, maintenance is inactive, so bypass is allowed
                MaintenanceMode::DISABLED => true,
                // In soft mode, bypass is allowed if the specific cookie is present
                MaintenanceMode::SOFT => array_key_exists(self::$COOKIE_KEY, $_COOKIE),
                // In enabled mode, maintenance is active, so no bypass is allowed
                MaintenanceMode::ENABLED => false
            };
        }

        // Sends a 503 Service Unavailable response with a maintenance page
        // The maintenance page can be customized by placing a maintenance.html template in the configured views directory
        // Otherwise, a default maintenance page included with the framework will be used
        private function sendMaintenanceResponse(): void {
            http_response_code(503);
            header('Content-Type: text/html; charset=UTF-8');
            header('Retry-After: 300');

            // Try to load custom maintenance page
            $templatePath = config('z_views') . DIRECTORY_SEPARATOR . 'maintenance.html';

            // Fallback to default template if custom one doesn't exist
            if(!file_exists($templatePath)) {
                $templatePath = zubzet()->z_framework_root . "IncludedComponents" . DIRECTORY_SEPARATOR . "views" . DIRECTORY_SEPARATOR . "maintenance.html";
            }

            include $templatePath;
            exit;
        }

        public static function isActive(): bool {
            return self::$mode !== MaintenanceMode::DISABLED;
        }

        public static function getMode(): string {
            return self::$mode;
        }
    }