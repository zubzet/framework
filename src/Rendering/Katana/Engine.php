<?php

    namespace ZubZet\Framework\Rendering\Katana;

    use Blade\View;
    use Blade\Blade;
    use Blade\Config;
    use ZubZet\Framework\Registry\Registry;

    /**
     * Thin adapter around the Katana Blade engine.
     *
     * The view roots (userspace, installed modules in order, framework) are registered as an
     * ordered finder chain via the Registry, so Katana resolves the entry view by name, its
     * @extends($layout) chain, @includes and <x-components> (including the framework's own
     * components/ under the framework root) with the same userspace-then-modules-then-framework
     * precedence the framework uses everywhere. A fresh Blade per render keeps @section state
     * from leaking across renders.
     *
     * Full rationale: docs/contributing/agents/working-with-agents.md (Render engine).
     */
    class Engine {

        /**
         * @internal
         * Escape hatch for prioritizing framework views. Caution: This WILL
         * be removed in future versions in favor of a modular view resolver.
         */
        public static bool $prioritizeFrameworkViews = false;

        public static function render(string $view, string $layout, array $data): View {
            $config = new Config(self::cachePath());

            // Ordered view roots: userspace first, then modules, framework last.
            $viewPaths = Registry::paths("views");

            // Prioritize framework views for testing: move the framework root first.
            if(self::$prioritizeFrameworkViews) {
                array_unshift($viewPaths, array_pop($viewPaths));
            }

            foreach($viewPaths as $viewPath) {
                $config->addViewPath($viewPath);
            }

            // Framework directives bound to the request (@auth / @guest, more later).
            Hooks::register($config);

            $blade = new Blade(config: $config);

            // Register the framework's own components under the "zubzet" namespace, so
            // <x-zubzet::head/> resolves to IncludedComponents/views/components/*.blade.php and
            // can neither shadow nor be shadowed by an app component (katanaphp/blade#66).
            $blade->addAnonymousComponentPath(
                zubzet()->z_framework_root . "IncludedComponents/views/components",
                "zubzet",
            );

            // The migrated view does @extends($layout); hand it the layout's view name.
            $data["layout"] = $layout;

            return $blade->render($view, $data);
        }

        /**
         * On-disk directory for compiled views, namespaced per project and per installed engine
         * reference. The engine segment forces a clean cache on upgrade (Katana keys a compiled
         * view on path + mtime, not engine version). Seam for a future generalized framework cache.
         */
        private static function cachePath(): string {
            try {
                $engine = (string) \Composer\InstalledVersions::getReference("katanaphp/blade");
            } catch(\Throwable $e) {
                $engine = "";
            }
            $projectKey = sha1(zubzet()->z_framework_root);
            $path = sys_get_temp_dir() . "/zubzet_cache/project_$projectKey/views/engine_$engine";
            if(!is_dir($path)) @mkdir($path, 0777, true);
            return $path;
        }
    }

?>
