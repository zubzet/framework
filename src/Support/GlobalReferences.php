<?php

    use z_framework;
    use z_model;
    use Request;
    use Response;
    use User;
    use z_db;

    use ZubZet\Framework\Core\FunctionConflictResolution;

    FunctionConflictResolution::require("zubzet");
    function zubzet(): z_framework {
        return z_framework::getInstance();
    }

    FunctionConflictResolution::require("model");
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

    FunctionConflictResolution::require("request");
    function request(): Request {
        return zubzet()->request;
    }

    FunctionConflictResolution::require("response");
    function response(): Response {
        return zubzet()->response;
    }

    FunctionConflictResolution::require("config");
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

    FunctionConflictResolution::require("user");
    function user(): User {
        return zubzet()->user;
    }

    FunctionConflictResolution::require("db");
    function db($connection = "default"): z_db {
        if("default" !== $connection) {
            throw new \InvalidArgumentException("Only the default connection is supported so far.");
        }
        return zubzet()->db;
    }

    FunctionConflictResolution::require("view");
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

?>