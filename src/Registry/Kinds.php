<?php

    namespace ZubZet\Framework\Registry;

    /**
     * Static table of the framework's built-in kinds. New kinds are added via
     * register(), so framework features (or later, modules) can join the
     * userspace -> modules -> framework resolution without new plumbing.
     */
    final class Kinds {

        /** @var Kind[] keyed by kind name */
        private static array $kinds = [];

        public static function get(string $name): Kind {
            self::bootstrap();
            if(!isset(self::$kinds[$name])) {
                throw new \InvalidArgumentException("Unknown registry kind: '$name'");
            }
            return self::$kinds[$name];
        }

        public static function register(Kind $kind): void {
            self::bootstrap();
            self::$kinds[$kind->name] = $kind;
        }

        private static function bootstrap(): void {
            if(!empty(self::$kinds)) return;
            foreach([
                new Kind("controllers", "z_controllers", null, "app/Controllers", "IncludedComponents/controllers/"),
                new Kind("models", "z_models", null, "app/Models", "IncludedComponents/models/"),
                // Views are consumed as directory roots only; the render engine
                // does the per-name lookup through its finder chain.
                new Kind("views", "z_views", null, "app/Views", "IncludedComponents/views"),
                // Symfony catalogue files
                new Kind("translations", "translations", null, "app/Translations", "IncludedComponents/translations", [".json", ".php", ".csv", ".ini"], deepFind: false),
                // deepFiles=false: keeps the historical flat glob("*.php") semantics.
                new Kind("routes", "routes", null, "app/Routes", "IncludedComponents/routes", deepFiles: false),
                // Framework commands are namespaced classes registered
                // explicitly in Console\Application; only userspace and
                // modules contribute by convention.
                new Kind("commands", "z_commands", null, "app/Commands", null),
                new Kind("migrations", null, "./app/Database/migrations", "app/Database/migrations", "IncludedComponents/database/Migration", [".sql", ".php"]),
                new Kind("seeds", null, "./app/Database/seed", "app/Database/seed", null, [".sql", ".php"]),
                // Assets: only moduleRoots() is consumed; the AssetProxy keeps its
                // own historical framework-first mount order.
                new Kind("assets", "z_frontend_root", null, "webroot", "IncludedComponents/assets/", []),
            ] as $kind) self::$kinds[$kind->name] = $kind;
        }
    }

?>
