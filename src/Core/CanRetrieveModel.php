<?php

    namespace ZubZet\Framework\Core;

    use ZubZet\Framework\Core\Model;
    use ZubZet\Framework\Registry\Registry;
    use ZubZet\Framework\Support\StaticCache;

    trait CanRetrieveModel {

        /**
         * Returns a model
         * @param string $model Name of the model
         * @param string $dir Set this when the model is stored in a specific directory
         * @return Model The model
         */
        public function getModel(string $model, ?string $dir = null) {
            $modelParts = explode(".", $model);

            if(count($modelParts) > 1) {
                $lastPart = array_pop($modelParts);
                $modelParts = array_map("strtolower", $modelParts);
                $model = implode(DIRECTORY_SEPARATOR, $modelParts) . DIRECTORY_SEPARATOR . $lastPart;
            }

            $model .= "Model";

            if(!is_null($dir)) {
                // Explicit directory override: unchanged legacy behavior.
                $path = $dir . "$model.php";
                if(!file_exists($path)) {
                    $path = config("z_framework_root") . "IncludedComponents/models/" . $model . ".php";
                }
                if(!file_exists($path)) {
                    throw new \Exception("Model: $model does not exist!");
                }
            } else {
                // Userspace -> modules -> framework; dot notation arrives as an
                // explicit sub-path, so it probes exact locations per root.
                $path = Registry::find("models", $model);
                if(is_null($path)) {
                    throw new \Exception("Model: $model does not exist!");
                }
            }

            // Cache key is the resolved file, so equally named models from
            // different roots can never alias to the wrong instance.
            $cacheKey = $path;

            if(StaticCache::has("model", $cacheKey)) {
                return StaticCache::get("model", $cacheKey);
            }

            // Only use the last part of the model name as the class Name
            $model = explode(DIRECTORY_SEPARATOR, $model);
            $model = array_pop($model);

            // Skip the require when the class already exists: a second include
            // of a same-named file from another root would be a fatal
            // redeclaration; first loaded wins for the request.
            if(!class_exists($model, false)) {
                require_once $path;
            }

            $modelInstance = new $model(db(), zubzet());
            return StaticCache::set("model", $cacheKey, $modelInstance);
        }

    }

?>