<?php 
    class SitemapController {

        public function action_index($req, $res) {

            $routes = [];

            $controllerFileList = glob($req->getZControllers()."*");
            if(!file_exists($req->getZControllers()."LoginController.php")) {
                $controllerFileList[] = $req->getZRoot().'default/controllers/LoginController.php';
            }

            foreach($controllerFileList as $filename){
                try {
                    require_once $filename;
                    $location_parts = explode("/", $filename);
                    $class_name = $location_parts[count($location_parts) - 1];
                    $class_name = str_replace(".php", "", $class_name);
                    foreach (get_class_methods($class_name) as $method) {

                        $public_flag = $method."_sitemap";

                        if(str_replace("action_", "", $method) != $method) {

                            $is_public = false;
                            if(isset($class_name::$$public_flag)) {
                                $is_public = $class_name::$$public_flag;
                            } else {
                                $is_public = $req->getBooterSettings("sitemapPublicDefault");
                            }

                            if($is_public) $routes[] = [
                                "class" => str_replace("Controller", "", $class_name),
                                "method" => str_replace("action_", "", $method)
                            ];
                        }
 
                    }
                } catch(Exception $ex) {
                    continue;
                }
            }

            header("Content-type: text/xml");

            echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

            foreach ($routes as $route) {
                echo "\t<url>\n";
                echo "\t\t<loc>";
                echo $req->getBooterSettings("host")."/";
                echo $req->getBooterSettings("rootDirectory");
                echo "$route[class]/";
                if($route["method"] != "index") {
                    echo "$route[method]/";
                }
                echo "</loc>\n";
                echo "\t</url>\n";
            }

            echo '</urlset>';

        }

    }
?>