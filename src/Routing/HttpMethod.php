<?php

    namespace ZubZet\Framework\Routing;

    trait HttpMethod {
        public static function any(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function get(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function post(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function put(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function delete(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function patch(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function options(string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, new PendingAction($action, $arguments));
        }

        public static function define(string $method, string $endpoint, array|callable $action, array $arguments = []): PendingRoute {
            return new PendingRoute($method, $endpoint, new PendingAction($action, $arguments));
        }
    }

?>