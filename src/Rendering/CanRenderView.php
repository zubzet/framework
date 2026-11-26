<?php

    namespace ZubZet\Framework\Rendering;

    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Message\EndOfRequest;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\ErrorHandling\DebugBar\DebugBarBridge;
    use ZubZet\Framework\Rendering\Katana\Engine;
    use ZubZet\Framework\Rendering\Resolver\ViewPath;

    use Blade\Exceptions\BladeException;

    trait CanRenderView {

        use ViewPath;

        /**
         * Shows a document to the user by echoing it (nothing is returned); a view that fails to render with a BladeException, e.g. a missing one, is rethrown unless showErrors is 0, which logs it and renders the 500 view with status 500 instead
         * @param string $document Path to the view
         * @param array $opt Associative array with values to replace in the view; response, request, user, root, host, absRoot and title are added to it as $opt in the view
         * @param string|array $options Rendering options, e.g., or a string for layout
         */
        public function render($document, $opt = [], $options = []) {
            $this->echoView($document, $opt, $options);

            // The view is the response, nothing of the action may run after it
            throw new EndOfRequest;
        }

        /**
         * Echoes a view like render() without ending the request, for output that is captured (mails)
         */
        private function echoView($document, $opt = [], $options = []): void {
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
            $standardOpt = self::standardOpt($opt);
            $data = array_merge(
                $opt,
                ["opt" => array_merge($opt, $standardOpt)],
            );

            try {
                // Render using Katana
                echo (string) Engine::render($viewName, $layoutName, $data, $standardOpt);
            } catch(BladeException $e) {
                // Like a controller exception: shown when errors are shown, otherwise logged as the 500 page
                if(0 != zubzet()->showErrors) throw $e;

                try {
                    logger(Logger::ZUBZET)->error(LogEventType::EXCEPTION, [
                        "class" => get_class($e),
                        "message" => $e->getMessage(),
                        "file" => $e->getFile(),
                        "line" => $e->getLine(),
                    ]);
                } catch(\Throwable) {}

                http_response_code(500);
                echo (string) Engine::render("500", "layout/min_layout", $data, $standardOpt);
            }
        }

        // The framework's own part of $opt. Views get it merged over what the caller
        // passed, components get exactly this as their $opt.
        private static function standardOpt(array $opt): array {
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

            return $expansion;
        }

    }

?>