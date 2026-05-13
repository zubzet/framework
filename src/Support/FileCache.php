<?php

    namespace ZubZet\Framework\Support;

    use DateInterval;
    use DateTimeImmutable;
    use Psr\SimpleCache\CacheInterface;

    /**
     * Filesystem-backed PSR-16 cache. Persists entries as
     * `<directory>/<sha1(key)>.cache` files holding a serialised
     * `['ttl_at' => int|null, 'value' => mixed]` payload.
     *
     * Subsystems that manage their own files (e.g. BladeOne's compile
     * directory) can grab a writable subfolder via `directoryFor($name)`.
     */
    class FileCache implements CacheInterface {

        private string $directory;

        public function __construct(string $directory) {
            $this->directory = rtrim($directory, '/\\');
            $this->ensureDirectory($this->directory);
        }

        public function directoryFor(string $name): string {
            $path = $this->directory . DIRECTORY_SEPARATOR . $name;
            $this->ensureDirectory($path);
            return $path;
        }

        public function remember(string $key, null|int|DateInterval $ttl, callable $compute): mixed {
            $sentinel = new \stdClass;
            $hit = $this->get($key, $sentinel);
            if($hit !== $sentinel) return $hit;
            $value = $compute();
            $this->set($key, $value, $ttl);
            return $value;
        }

        public function get(string $key, mixed $default = null): mixed {
            $this->assertValidKey($key);
            $file = $this->fileFor($key);
            if(!is_file($file)) return $default;

            $payload = @unserialize(@file_get_contents($file));
            if(!is_array($payload) || !array_key_exists('value', $payload)) {
                return $default;
            }
            if($payload['ttl_at'] !== null && time() > $payload['ttl_at']) {
                @unlink($file);
                return $default;
            }
            return $payload['value'];
        }

        public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool {
            $this->assertValidKey($key);
            $payload = serialize([
                'ttl_at' => $this->resolveTtlAt($ttl),
                'value' => $value,
            ]);
            $file = $this->fileFor($key);
            $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if(false === @file_put_contents($tmp, $payload, LOCK_EX)) return false;
            if(!@rename($tmp, $file)) {
                @unlink($tmp);
                return false;
            }
            return true;
        }

        public function delete(string $key): bool {
            $this->assertValidKey($key);
            $file = $this->fileFor($key);
            if(!is_file($file)) return true;
            return @unlink($file);
        }

        public function clear(): bool {
            $ok = true;
            foreach(glob($this->directory . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
                $ok = @unlink($f) && $ok;
            }
            return $ok;
        }

        public function getMultiple(iterable $keys, mixed $default = null): iterable {
            $out = [];
            foreach($keys as $k) {
                $out[$k] = $this->get($k, $default);
            }
            return $out;
        }

        public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool {
            $ok = true;
            foreach($values as $k => $v) {
                $ok = $this->set((string) $k, $v, $ttl) && $ok;
            }
            return $ok;
        }

        public function deleteMultiple(iterable $keys): bool {
            $ok = true;
            foreach($keys as $k) {
                $ok = $this->delete($k) && $ok;
            }
            return $ok;
        }

        public function has(string $key): bool {
            $sentinel = new \stdClass;
            return $this->get($key, $sentinel) !== $sentinel;
        }

        private function ensureDirectory(string $path): void {
            if(is_dir($path)) return;
            if(@mkdir($path, 0775, true)) return;
            if(is_dir($path)) return;
            throw new \RuntimeException("Cache directory '$path' could not be created.");
        }

        private function fileFor(string $key): string {
            return $this->directory . DIRECTORY_SEPARATOR . sha1($key) . '.cache';
        }

        private function assertValidKey(string $key): void {
            if($key === '' || preg_match('#[{}()/\\\\@:]#', $key)) {
                throw new \InvalidArgumentException("Invalid PSR-16 cache key: '$key'");
            }
        }

        private function resolveTtlAt(null|int|DateInterval $ttl): ?int {
            if($ttl === null) return null;
            if($ttl instanceof DateInterval) {
                return (new DateTimeImmutable())->add($ttl)->getTimestamp();
            }
            return time() + $ttl;
        }

    }

?>
