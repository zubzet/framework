<?php

    namespace ZubZet\Framework\Routing;

    trait HttpMethod {
        public static function any(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function get(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function post(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function put(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function delete(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function patch(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function options(string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute(__FUNCTION__, $endpoint, $action);
        }

        public static function define(string $method, string $endpoint, array|callable $action): PendingRoute {
            return new PendingRoute($method, $endpoint, $action);
        }
    }

?>