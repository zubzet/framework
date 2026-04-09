<?php

    namespace ZubZet\Framework\Core;

    use ZubZet\Framework\Core\Model;
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

            // Cache key is the model name with "Model" suffix. Its for caching the model instance
            // If the model is in a subdirectory, the cache key will be the model name with the subdirectory path.
            // This is to avoid cache conflicts between models with the same name in different directories.
            $cacheKey = $model;
            $path = !is_null($dir) ? $dir : config("z_models");
            $path .= "$model.php";

            if(StaticCache::has("model", $cacheKey)) {
                return StaticCache::get("model", $cacheKey);
            }

            if(file_exists($path)) {
                require_once $path;
            } else {
                $path = config("z_framework_root") . "IncludedComponents/models/" . $model . ".php";
                if(!file_exists($path)) {
                    throw new \Exception("Model: $model does not exist!");
                }
                require_once $path;
            }

            // Only use the last part of the model name as the class Name
            $model = explode(DIRECTORY_SEPARATOR, $model);
            $model = array_pop($model);

            $modelInstance = new $model(db(), zubzet());
            return StaticCache::set("model", $cacheKey, $modelInstance);
        }

    }

?>