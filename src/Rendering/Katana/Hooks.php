<?php

    namespace ZubZet\Framework\Rendering\Katana;

    use Blade\Config;

    /**
     * Framework hooks bound into the Katana engine: directives and callbacks tied to the
     * request context. Today this is only @auth / @guest, but this is the home for every
     * future framework directive/callback, so it is expected to grow.
     *
     * See docs/contributing/agents/working-with-agents.md (Render engine).
     */
    class Hooks {

        /** Wire all framework hooks into a Katana Config. */
        public static function register(Config $config): void {
            $config->setAuthCallback(self::auth());
        }

        // @auth and @guest bound to the current request. The single argument is a framework
        // permission (not a Laravel guard); @guest is the negation.
        private static function auth(): \Closure {
            return function(?string $permission = null): bool {
                $user = user();
                if(!$user || !$user->isLoggedIn) return false;
                if(empty($permission)) return true;
                return $user->checkPermission($permission);
            };
        }
    }

?>
