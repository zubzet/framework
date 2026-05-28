<?php

    namespace ZubZet\Framework\Rendering;

    /**
     * Default-layout management for Response.
     *
     * Resolution order used by Response::render() when no explicit layout
     * is passed to render():
     *   1. top of the per-instance stack  (`setDefaultLayout` / `pushDefaultLayout`)
     *   2. top of the request-wide stack  (`setGlobalDefaultLayout` / `pushGlobalDefaultLayout`)
     *   3. framework default               ("layout/default_layout.php")
     *
     * Both scopes expose a `set*` (clear + push), `push*` and `pop*` so a
     * "part of the app" - admin area, nested component - can install its
     * own default and restore the previous one when done.
     */
    trait HandlesDefaultLayout {

        private static array $globalDefaultLayoutStack = [];
        private array $instanceDefaultLayoutStack = [];

        public static function setGlobalDefaultLayout(string $layout): void {
            self::$globalDefaultLayoutStack = [$layout];
        }

        public static function pushGlobalDefaultLayout(string $layout): void {
            self::$globalDefaultLayoutStack[] = $layout;
        }

        public static function popGlobalDefaultLayout(): string {
            if (empty(self::$globalDefaultLayoutStack)) {
                throw new \UnderflowException(
                    "popGlobalDefaultLayout called on an empty stack - every push must be matched by a single pop."
                );
            }
            return array_pop(self::$globalDefaultLayoutStack);
        }

        public function setDefaultLayout(string $layout): void {
            $this->instanceDefaultLayoutStack = [$layout];
        }

        public function pushDefaultLayout(string $layout): void {
            $this->instanceDefaultLayoutStack[] = $layout;
        }

        public function popDefaultLayout(): string {
            if (empty($this->instanceDefaultLayoutStack)) {
                throw new \UnderflowException(
                    "popDefaultLayout called on an empty stack - every push must be matched by a single pop."
                );
            }
            return array_pop($this->instanceDefaultLayoutStack);
        }

        protected function resolveDefaultLayout(): string {
            if (!empty($this->instanceDefaultLayoutStack)) {
                return $this->instanceDefaultLayoutStack[array_key_last($this->instanceDefaultLayoutStack)];
            }
            if (!empty(self::$globalDefaultLayoutStack)) {
                return self::$globalDefaultLayoutStack[array_key_last(self::$globalDefaultLayoutStack)];
            }
            return "layout/default_layout.php";
        }

    }

?>
