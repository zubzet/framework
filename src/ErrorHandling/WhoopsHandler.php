<?php

    namespace ZubZet\Framework\ErrorHandling;

    use ZubZet\Framework\ErrorHandling\GenericException\NotInstantiatedException;

    use Whoops\Run;
    use Whoops\Handler\PrettyPageHandler;

    class WhoopsHandler {

        public Run $run;

        private const SENSITIVE_KEY_PATTERNS = [
            'pass', 'secret', 'token', 'session', 'auth',
            'access', 'key', 'credential', 'private', 'bearer',
        ];

        private const MASKED_SUPERGLOBALS = [
            '_GET', '_POST', '_COOKIE', '_SESSION', '_SERVER', '_ENV',
        ];

        public function __construct() {
            if(config("execution_type", default: "prod") !== "test") return;

            $this->run = new Run();

            $handler = new PrettyPageHandler();

            $containerAppPath = getcwd() . DIRECTORY_SEPARATOR;

            $this->configureEditorLink($handler, $containerAppPath);
            $this->configureApplicationPaths($handler, $containerAppPath);
            $this->maskSensitiveSuperglobalKeys($handler);

            $this->run->pushHandler($handler);
            $this->run->register();
        }

        private function configureEditorLink(PrettyPageHandler $handler, string $containerAppPath): void {
            $pwd = config("automated_host_working_directory");
            if(is_null($pwd) || empty($pwd)) return;

            $hostAppPath = rtrim($pwd, "/") . "/";
            $handler->setEditor(function($file, $line) use ($containerAppPath, $hostAppPath) {
                if(!str_starts_with($file, $containerAppPath)) return null;
                $relative = substr($file, strlen($containerAppPath));
                $editor = config("development_editor", default: "vscode");
                return "$editor://file/{$hostAppPath}{$relative}:{$line}";
            });
        }

        private function configureApplicationPaths(PrettyPageHandler $handler, string $containerAppPath): void {
            $handler->setApplicationPaths([
                $containerAppPath . "app" . DIRECTORY_SEPARATOR,
            ]);
        }

        private function maskSensitiveSuperglobalKeys(PrettyPageHandler $handler): void {
            foreach(self::MASKED_SUPERGLOBALS as $superglobal) {
                // Whoops's hideSuperglobalKey API uses the underscored
                // PHP name (_GET, _POST, ...); Input\State drops the
                // underscore. Strip it when reading the live request, and
                // fall back to $GLOBALS for anything Input\State doesn't
                // model (e.g. _ENV) or when request() isn't ready yet.
                $inputProperty = ltrim($superglobal, '_');
                try {
                    $values = request()->input->{$inputProperty};
                } catch(\Throwable) {
                    $values = $GLOBALS[$superglobal] ?? null;
                }

                if(!is_array($values)) continue;

                foreach(array_keys($values) as $key) {
                    if(!self::keyLooksSensitive($key)) continue;
                    $handler->hideSuperglobalKey($superglobal, $key);
                }
            }
        }

        private static function keyLooksSensitive(string $key): bool {
            $needle = strtolower($key);
            foreach(self::SENSITIVE_KEY_PATTERNS as $pattern) {
                if(str_contains($needle, $pattern)) return true;
            }
            return false;
        }
    }

?>
