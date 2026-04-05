<?php

    namespace ZubZet\Framework\Bootstrap;

    use ZubZet\Framework\Support\HasDynamicAttributes;

    trait Configuration {

        use HasDynamicAttributes;

        public function loadConfiguration(string $frameworkRoot, array $params) {
            $this->setAttributes([
                "z_controllers" => "app/Controllers/",
                "z_models" => "app/Models/",
                "z_views" => "app/Views/",
                "routes" => "app/Routes/",
                "config_file" => "z_config/z_settings.ini",
                "z_framework_root" => $frameworkRoot,
            ]);

            //Parse ini file with inline comments ignored
            $ini_data = file_get_contents($this->config_file);
            $ini_data = str_replace(";", "-----semicolon-----", $ini_data);
            $ini_data = str_replace("#", "-----hashtag-----", $ini_data);

            $config = parse_ini_string($ini_data);
            foreach($config as $key => $value) {
                $value = str_replace("-----semicolon-----", ";", $value);
                $value = str_replace("-----hashtag-----", "#", $value);
                $config[$key] = $value;
            }

            $this->overwriteAttributes($config);

            $this->rootFolder = "/".$this->rootDirectory;
            $this->root = $this->host . "/" . $this->rootDirectory;

            //Replace config file with code settings
            foreach($params as $key => $param) {
                if(isset($this->{$key})) {
                    $this->{$key} = $param;
                }
            }

            //Overwrite using environment vars
            if($this->allow_env_config ?? false) {
                foreach($this->getAllAttributes() as $key => $setting) {
                    $envName = "CONFIG_".strtoupper($key);
                    if(false !== getenv($envName)) {
                        $this->{$key} = getenv($envName);
                    }
                }
            }

        }

    }

?>