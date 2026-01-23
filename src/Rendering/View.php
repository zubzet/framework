<?php

    namespace ZubZet\Framework\Rendering;

    trait View {

        /**
         * @internal
         * Returns the path of a view. If the view does not exist, this function will fall back to the framework defaults.
         * @param string $document Filename of the views
         * @return string Relative path to the view file
         */
        public static function resolvePath(string $document): string {
            if(substr($document, -4, 4) != ".php") {
                $document .= ".php";
            }
            if (file_exists(zubzet()->z_views.$document)) {
                return zubzet()->z_views.$document;
            }
            if (file_exists(zubzet()->z_framework_root."IncludedComponents/views/$document")) {
                return zubzet()->z_framework_root."IncludedComponents/views/$document";
            }
            return zubzet()->z_framework_root."IncludedComponents/views/500.php";
        }

        /**
         * Shows a document to the user
         * @param string $document Path to the view
         * @param array $opt Associative array with values to replace in the view
         * @param string|array $options Rendering options, e.g., or a string for layout
         */
        public function render($document, $opt = [], $options = []) {
            // Legacy as $options used to be $layout
            if(!is_array($options)) {
                $options = [
                    "layout" => $options
                ];
            }

            $layout = $options["layout"] ?? "layout/default_layout.php";
            $viewPath = self::resolvePath($document);

            //Set default parameter values
            $opt["response"] = $this;
            $opt["request"] = $this->booter->req;
            $opt["root"] = $this->booter->rootFolder;
            $opt["host"] = $this->booter->host;
            $opt["absRoot"] = $this->booter->host.$this->booter->rootFolder;

            if (!isset($opt["title"])) $opt["title"] = $this->getBooterSettings("pageName");

            //logged in user information
            $opt["user"] = $this->booter->user;

            include_once zubzet()->z_framework_root."IncludedComponents/views/layout/layout_essentials.php";
            $opt["layout_essentials_body"] = function($opt) {
                essentialsBody($opt);
            };
            $opt["layout_essentials_head"] = function($opt, $customBootstrap = false) {
                essentialsHead($opt, $customBootstrap);
            };

            // Optional log view
            try {
                $catId = model("z_general")->getLogCategoryIdByName("view");
                $location = $_SERVER['REQUEST_URI'] ?? "console";
                model("z_general")->logAction(
                    $catId,
                    "URL viewed (User ID: " . user()->userId . " , URL: $location)",
                    $document,
                );
            } catch (\Exception $e) {
                // Do not log this render to avoid having to require a database
            }

            //Load the document
            $view = include($viewPath);

            //Load the layout
            $layout_url = $layout;
            $layout = include(self::resolvePath($layout));

            $opt["generateResourceLink"] = function($url, $root = true) {
                $v = $this->getBooterSettings("assetVersion");
                echo (($root ? $this->booter->rootFolder : "") . $url . "?v=" . (($v == "dev") ? time() : $v));
            };

            $opt["echo"] = function($val) {
                echo nl2br(htmlspecialchars($val));
            };

            //Makes $body and $head optional
            if(!isset($view["body"])) $view["body"] = function(){};
            if(!isset($view["head"])) $view["head"] = function(){};

            ob_start();
            $layout["layout"]($opt, $view["body"], $view["head"]);
            $rendered = ob_get_contents();
            ob_end_clean();

            echo $rendered;
        }

    }

?>