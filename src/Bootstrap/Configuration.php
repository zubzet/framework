<?php

    namespace ZubZet\Framework\Bootstrap;

    class Configuration {

        public array $settings;

        public function __construct(string $frameworkRoot, array $params) {
            $this->settings = [
                "z_controllers" => "app/Controllers/",
                "z_models" => "app/Models/",
                "z_views" => "app/Views/",
                "routes" => "app/Routes/",
                "config_file" => "z_config/z_settings.ini",
                "z_framework_root" => $frameworkRoot,
                "root" => $frameworkRoot,
            ];

            //Parse ini file with inline comments ignored
            $ini_data = file_get_contents($this->settings["config_file"]);
            $ini_data = str_replace(";", "-----semicolon-----", $ini_data);
            $ini_data = str_replace("#", "-----hashtag-----", $ini_data);

            $config = parse_ini_string($ini_data);
            foreach($config as $key => $value) {
                $value = str_replace("-----semicolon-----", ";", $value);
                $value = str_replace("-----hashtag-----", "#", $value);
                $config[$key] = $value;
            }

            $this->settings = array_merge($this->settings, $config);

            //Replace config file with code settings
            foreach($params as $key => $param) {
                if(isset($this->settings[$key])) {
                    $this->settings[$key] = $param;
                }
            }

            //Overwrite using environment vars
            if($this->settings["allow_env_config"] ?? false == true) {
                foreach($this->settings as $key => $setting) {
                    $envName = "CONFIG_".strtoupper($key);
                    if(false !== getenv($envName)) {
                        $this->settings[$key] = getenv($envName);
                    }
                }
            }

        }

        public function __set(string $name, mixed $value): void {
            $this->settings[$name] = $value;
        }

        public function __get(string $name): mixed {
            if(!isset($this->settings[$name])) return null;
            return $this->settings[$name];
        }

        public function __isset(string $name): bool {
            return isset($this->settings[$name]);
        }

    }

?>