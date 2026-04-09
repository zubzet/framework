<?php

    namespace ZubZet\Framework\Support;

    final class StaticCache {

        private static array $cache = [];

        public static function set(string $type, string $key, mixed $value): mixed {
            $cacheType = &self::$cache[$type];
            if(!isset($cacheType)) $cacheType = [];
            $cacheType[$key] = $value;
            return $value;
        }

        public static function get(string $type, string $key): mixed {
            if(!isset(self::$cache[$type])) {
                throw new \InvalidArgumentException("The type '$type' does not exist in the cache.");
            }
            if(!isset(self::$cache[$type][$key])) {
                throw new \InvalidArgumentException("The key '$key' does not exist in the cache.");
            }
            return self::$cache[$type][$key];
        }

        public static function has(string $type, string $key): bool {
            return isset(self::$cache[$type][$key]);
        }

    }
?>