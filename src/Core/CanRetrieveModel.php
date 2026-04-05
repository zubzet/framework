<?php

    namespace ZubZet\Framework\Core;

    use ZubZet\Framework\Core\Model;

    trait CanRetrieveModel {

        /** @var \z_model[] Stores all already used models for this request */
        private $modelCache = [];

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
            $path = !is_null($dir) ? $dir : config("z_models");
            $path .= "$model.php";

            if(isset($this->modelCache[$model])) {
                return $this->modelCache[$model];
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

            $this->modelCache[$model] = new $model(db(), zubzet());
            return $this->modelCache[$model];
        }

    }

?>