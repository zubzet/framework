<?php

    namespace ZubZet\Framework\Support\RuntimeCache;

    /**
     * Remembers values for as long as the PHP script runs: one request, one console command,
     * or the whole life of a worker. Nothing is stored anywhere and nothing reaches another
     * request, this is memoization and not a persistent cache.
     * A class that extends it has a store of its own: what it holds is out of reach for
     * RuntimeCache and for every other class extending it, whatever types and keys they use.
     */
    class RuntimeCache {

        /** @var array<class-string, array<string, array<string, mixed>>> One store per class */
        private static array $cache = [];

        public static function set(string $type, string $key, mixed $value): mixed {
            self::$cache[static::class][$type][$key] = $value;
            return $value;
        }

        public static function get(string $type, string $key, bool $allowNull = false): mixed {
            $store = self::$cache[static::class] ?? [];

            if(!isset($store[$type])) {
                if($allowNull) return null;
                throw new \InvalidArgumentException("The type '$type' does not exist in the cache.");
            }
            if(!isset($store[$type][$key])) {
                if($allowNull) return null;
                throw new \InvalidArgumentException("The key '$key' does not exist in the cache.");
            }
            return $store[$type][$key];
        }

        public static function getOrNull(string $type, string $key): mixed {
            return static::get($type, $key, true);
        }

        public static function has(string $type, string $key): bool {
            return isset(self::$cache[static::class][$type][$key]);
        }

    }
?>
