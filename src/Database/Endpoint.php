<?php

    namespace ZubZet\Framework\Database;

    /**
     * Where and how the database is reached, read from the booter settings
     * and shared by the runtime connection and the migrations' Doctrine DBAL
     * connection: `dbhost`, `dbport` and the transport encryption (`db_ssl`).
     */
    class Endpoint {

        private static ?Endpoint $instance = null;

        public ?string $host;
        public ?int $port;
        public bool $tls;

        /** The endpoint is read from the configuration once per request. */
        public static function get(): self {
            if(is_null(self::$instance)) self::$instance = new self();
            return self::$instance;
        }

        private function __construct() {
            $this->host = config("dbhost");
            $port = config("dbport", default: null);
            $this->port = is_numeric($port) ? (int) $port : null;
            $this->tls = filter_var(config("db_ssl", default: false), FILTER_VALIDATE_BOOLEAN);

            // The certificate is matched against dbhost as-is, so a
            // "host:port" value could never verify.
            if($this->tls && str_contains($this->host, ":")) {
                throw new \RuntimeException(
                    "db_ssl verifies the server certificate against 'dbhost', which must be "
                    . "a plain hostname: move the port into 'dbport'."
                );
            }
        }

        /**
         * Prepares a not-yet-connected mysqli handle for the configured
         * transport and returns the flags for real_connect(). Plaintext
         * connections are left untouched.
         */
        public function applyTls(\mysqli $connection): int {
            if(!$this->tls) return 0;

            [$ca, $caPath] = $this->trustStore();
            $connection->ssl_set(null, null, $ca, $caPath, null);
            return MYSQLI_CLIENT_SSL;
        }

        /**
         * The system trust store, as a [file, directory] pair. mysqli only
         * verifies the server certificate when handed an authority and never
         * resolves one itself; without this, db_ssl would silently accept
         * any certificate. A private authority is added to the system store,
         * or configured PHP-wide via openssl.cafile.
         */
        public function trustStore(): array {
            $locations = openssl_get_cert_locations();

            $file = $locations["ini_cafile"] ?: $locations["default_cert_file"];
            if(is_readable($file)) return [$file, null];

            $directory = $locations["ini_capath"] ?: $locations["default_cert_dir"];
            if(is_dir($directory)) return [null, $directory];

            throw new \RuntimeException(
                "db_ssl is enabled but no system trust store was found to verify the server "
                . "against. Install your distribution's CA bundle or set openssl.cafile in php.ini."
            );
        }

    }

?>
