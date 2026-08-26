<?php

    namespace ZubZet\Framework\Support {
        class GlobalReferences { }
    }

    namespace {

        use ZubZet\Framework\ZubZet;
        use ZubZet\Framework\Logger\Logger;
        use ZubZet\Framework\Message\Request;
        use ZubZet\Framework\Message\Response;
        use ZubZet\Framework\Authentication\User;
        use ZubZet\Framework\Database\Connection;
        use ZubZet\Framework\Logger\LoggerFactory;
        use ZubZet\Framework\Translation\Translation;
        use ZubZet\Framework\Core\FunctionConflictResolution;
        use ZubZet\Framework\ErrorHandling\GenericException\NotInstantiatedException;

        FunctionConflictResolution::requireAndThen("zubzet", function() {
            /**
             * Proxy to the framework`s instance
             *
             * @return ZubZet ZubZet instance
             */
            function zubzet(): ZubZet {
                if(ZubZet::$instance instanceof ZubZet) return ZubZet::$instance;
                throw new NotInstantiatedException("ZubZet (The framework itself)");
            }
        });

        FunctionConflictResolution::requireAndThen("model", function() {
            /**
             * Proxy to the framework's model loader.
             *
             * @param string $model Name of the model to load
             * @param string|null $dir Optional directory override
             * @return z_model Loaded model instance
             */
            function model($model, $dir = null) {
                return zubzet()->getModel($model, $dir);
            }
        });

        FunctionConflictResolution::requireAndThen("request", function() {
            /**
             * Proxy to the framework`s request instance
             *
             * @return Request Loaded request instance
             */
            function request(): Request {
                if(zubzet()->req instanceof Request) return zubzet()->req;
                throw new NotInstantiatedException("Request");
            }
        });

        FunctionConflictResolution::requireAndThen("response", function() {
            /**
             * Proxy to the framework`s response instance
             *
             * @return Response Loaded response instance
             */
            function response(): Response {
                return zubzet()->res;
            }
        });

        FunctionConflictResolution::requireAndThen("config", function() {
            /**
             * Fetches a configuration value exposed by the request handler.
             *
             * @param string|null $key Setting key or null to retrieve all settings
             * @param bool $useDefault Whether to fall back to the provided default value
             * @param mixed $default Value returned when the key is missing and $useDefault is true
             * @return mixed Configuration value or array of all settings
             */
            function config($key = null, $useDefault = true, $default = null) {
                return zubzet()->getBooterSettings($key, $useDefault, $default);
            }
        });

        FunctionConflictResolution::requireAndThen("configNumeric", function() {
            /**
             * Fetches a configuration value that has to be a number.
             *
             * Non-numeric values are rejected instead of cast, where a typo or
             * a value like "off" would silently become 0 and disable whatever
             * the setting controls.
             *
             * @param string $key Setting key
             * @param int $default Value used when the key is missing
             * @throws \InvalidArgumentException When the configured value is not numeric
             * @return int Configured value as an integer
             */
            function configNumeric(string $key, int $default): int {
                $value = config($key, default: $default);
                if(!is_numeric($value)) {
                    throw new \InvalidArgumentException("Config key '$key' must be numeric, got: '$value'");
                }
                return (int) $value;
            }
        });

        FunctionConflictResolution::requireAndThen("user", function() {
            /**
             * Proxy to the currently logged-in user's object
             *
             * @return User The currently logged-in user
             */
            function user(): ?User {
                return zubzet()->user;
            }
        });

        FunctionConflictResolution::requireAndThen("db", function() {
            /**
             * Proxy to the loaded database connection
             *
             * @throws InvalidArgumentException If a non-default connection is requested.
             * @return ?Connection Loaded database connection
             */
            function db(string $connection = "default", bool $allowUnsetConnection = false): ?Connection {
                if("default" !== $connection) {
                    throw new \InvalidArgumentException("Only the default connection is supported so far.");
                }
                if($allowUnsetConnection && !isset(zubzet()->z_db)) {
                    return null;
                }
                if(!(zubzet()->z_db instanceof Connection)) {
                    throw new NotInstantiatedException("Connection (Database)");
                }
                return zubzet()->z_db;
            }
        });

        FunctionConflictResolution::requireAndThen("view", function() {
            /**
             * Renders a view using the active response instance.
             *
             * @param string $document Path or identifier of the view
             * @param array $opt Variables passed into the view template
             * @param array|string $options Rendering options or layout identifier
             * @return void
             */
            function view(string $document, array $opt = [], array|string $options = []) {
                return response()->render($document, $opt, $options);
            }
        });

        FunctionConflictResolution::requireAndThen("logger", function() {
            function logger(?string $name = null): Logger {
                return LoggerFactory::getOrCreateLogger($name ?? Logger::APP);
            }
        });

        FunctionConflictResolution::requireAndThen("t", function() {
            /**
             * Translates a message id. An id no catalogue defines is returned unchanged.
             *
             * @param string $id Message id, e.g. "dashboard.welcome"
             * @param array $parameters Placeholder values, spelled the way the catalogue spells them
             * @param string|null $domain Catalogue domain, "messages" when omitted
             * @param string|null $locale Locale override, the active locale when omitted
             * @return string The translated message
             */
            function t(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
                return Translation::translate($id, $parameters, $domain, $locale);
            }
        });

        FunctionConflictResolution::requireAndThen("isCli", function() {
            function isCli(): bool {
                return php_sapi_name() === "cli";
            }
        });
    }

?>