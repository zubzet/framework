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

        public static function get(string $type, string $key, bool $allowNull = false): mixed {
            if(!isset(self::$cache[$type])) {
                if($allowNull) return null;
                throw new \InvalidArgumentException("The type '$type' does not exist in the cache.");
            }
            if(!isset(self::$cache[$type][$key])) {
                if($allowNull) return null;
                throw new \InvalidArgumentException("The key '$key' does not exist in the cache.");
            }
            return self::$cache[$type][$key];
        }

        public static function getOrNull(string $type, string $key): mixed {
            return self::get($type, $key, true);
        }

        public static function has(string $type, string $key): bool {
            return isset(self::$cache[$type][$key]);
        }

    }
?>