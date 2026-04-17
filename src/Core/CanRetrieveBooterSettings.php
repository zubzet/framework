<?php

    namespace ZubZet\Framework\Core;

    trait CanRetrieveBooterSettings {

        /**
         * Gets a booter setting
         * @param string $key Key of the setting
         * @return mixed Value of the key
         */
        public function getBooterSettings($key = null, $useDefault = true, $default = null) {
            // Return all settings if no key is provided
            if(empty($key)) return zubzet()->getAllAttributes();

            // Return specific setting if it exists
            if(isset(zubzet()->{$key})) return zubzet()->{$key};

            // Return default if enabled
            if($useDefault) return $default;

            throw new \InvalidArgumentException("The setting '$key' does not exist!");
        }

    }

?>