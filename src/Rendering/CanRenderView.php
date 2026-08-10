<?php

    namespace ZubZet\Framework\Rendering;

    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge;
    use ZubZet\Framework\Rendering\Katana\Engine;
    use ZubZet\Framework\Rendering\Resolver\ViewPath;

    use Blade\Exceptions\BladeException;

    trait CanRenderView {

        use ViewPath;

        /**
         * Shows a document to the user
         * @param string $document Path to the view
         * @param array $opt Associative array with values to replace in the view
         * @param string|array $options Rendering options, e.g., or a string for layout
         */
        public function render($document, $opt = [], $options = []) {
            // Legacy as $options used to be $layout
            if(!is_array($options)) {
                $options = ["layout" => $options];
            }

            $layout = $options["layout"] ?? $this->resolveDefaultLayout();
            $layoutName = self::viewName($layout);
            $viewName = self::viewName($document);

            // Optional log view
            try {
                logger(Logger::ZUBZET)->info(LogEventType::RENDER, [
                    "location" => implode("/", request()->getUrlParts()),
                    "view" => $document,
                    "viewName" => $viewName,
                    "layout" => $layout,
                    "layoutName" => $layoutName,
                ]);
            } catch (\Exception $e) {
                // Do not log this render to avoid having to require a database
            }

            // The debug bar shows the reference the caller passed (with its
            // extension), not the extension-stripped Katana name, so a render of
            // "login.php" reads as "login.php" and the layout keeps its ".php".
            DebugBarBridge::collectTemplate($document, $opt, "blade", $layout);

            // Expand legacy $opt with framework variables, functions, and objects
            // likely subject to deprecation in the future as the expansions overwrite
            // the provided opt parameters.
            $data = array_merge($opt, self::legacyOptExpansion($opt));

            try {
                // Render using Katana
                echo (string) Engine::render($viewName, $layoutName, $data);
            } catch(BladeException $e) {
                // Render the 500 page when a view is not found
                echo (string) Engine::render("500", "layout/min_layout", $data);
            }
        }

        private static function legacyOptExpansion(array $opt): array {
            // Classes
            $expansion["response"] = response();
            $expansion["request"] = request();
            $expansion["user"] = user();

            // Variables
            $expansion["root"] = zubzet()->rootFolder;
            $expansion["host"] = zubzet()->host;
            $expansion["absRoot"] = zubzet()->host . zubzet()->rootFolder;
            $expansion["title"] = $opt["title"] ?? config("pageName", default: "ZubZet");

            // Functions
            $expansion["generateResourceLink"] = function($url, $root = true) {
                $version = config("assetVersion");
                if("dev" == $version) $version = time();
                echo ($root ? zubzet()->rootFolder : "") . "{$url}?v={$version}";
            };

            $expansion["echo"] = function($val) {
                echo nl2br(htmlspecialchars($val));
            };

            // All variables used to be found in $opt
            return ["opt" => array_merge($opt, $expansion)];
        }

    }

?>