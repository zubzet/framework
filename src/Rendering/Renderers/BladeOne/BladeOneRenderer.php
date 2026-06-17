<?php

    namespace ZubZet\Framework\Rendering\Renderers\BladeOne;

    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Rendering\Renderer;

    /** Renders `*.blade.php` views via eftec/bladeone. */
    class BladeOneRenderer implements Renderer {

        private array $templatePaths;
        private ZubZetBladeOne $blade;

        public function __construct(array $templatePaths, string $compileDir) {
            $this->templatePaths = $templatePaths;
            $this->blade = new ZubZetBladeOne($this->templatePaths, $compileDir, ZubZetBladeOne::MODE_AUTO);

            $this->registerAuthorization();
        }

        public function supports(string $viewPath): bool {
            return str_ends_with($viewPath, '.blade.php');
        }

        public function render(string $viewPath, array $opt): string {
            return $this->blade->run($this->resolveViewName($viewPath), $opt);
        }

        public function blade(): ZubZetBladeOne {
            return $this->blade;
        }

        private function resolveViewName(string $viewPath): string {
            foreach($this->templatePaths as $base) {
                if(!str_starts_with($viewPath, $base)) continue;
                $relative = substr($viewPath, strlen($base), -strlen('.blade.php'));
                return str_replace([DIRECTORY_SEPARATOR, '/'], '.', $relative);
            }
            throw new \RuntimeException("BladeOne template '$viewPath' is not under any registered template path.");
        }

        private function registerAuthorization(): void {
            $this->blade->setCanFunction(function($permission): bool {
                return user()->checkPermission($permission);
            });

            $this->blade->setAnyFunction(function(...$permissions): bool {
                if(!user()->isLoggedIn) return false;

                $user = User::byId(user()->userId);

                return $user->hasAccessAnyOf($permissions);
            });

            if(!user()->isLoggedIn) {
                $this->blade->setAuth(null, null, []);
                return;
            }

            $this->blade->setAuth(user()->userId, null, []);
        }

    }

?>
