<?php

    use ZubZet\Framework\Core\FunctionConflictResolution;

    FunctionConflictResolution::requireAndThen("zubzet", function() {
        /**
         * Proxy to the framework`s instance
         *
         * @return z_framework ZubZet instance
         */
        function zubzet(): z_framework {
            $instance = z_framework::getInstance();
            if(!is_null($instance)) return $instance;

            throw new RuntimeException("The ZubZet framework instance is not yet available.");
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
            return zubzet()->req;
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
            return request()->getBooterSettings($key, $useDefault, $default);
        }
    });

    FunctionConflictResolution::requireAndThen("user", function() {
        /**
         * Proxy to the currently logged-in user's object
         *
         * @return User The currently logged-in user
         */
        function user(): User {
            return zubzet()->user;
        }
    });

    FunctionConflictResolution::requireAndThen("db", function() {
        /**
         * Proxy to the loaded database connection
         *
         * @throws InvalidArgumentException If a non-default connection is requested.
         * @return z_db Loaded database connection
         */
        function db($connection = "default"): z_db {
            if("default" !== $connection) {
                throw new \InvalidArgumentException("Only the default connection is supported so far.");
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
        function view($document, $opt = [], $options = []) {
            return response()->render($document, $opt, $options);
        }
    });

?>