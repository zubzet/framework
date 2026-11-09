<?php

    namespace ZubZet\Framework\Core;

    trait CanRetrieveBooterSettings {

        /**
         * Gets a booter setting
         * @param string|null $key Key of the setting, null or empty returns all settings
         * @param bool $useDefault Return $default instead of throwing when the key does not exist
         * @param mixed $default Value returned for a missing key when $useDefault is true
         * @return mixed Value of the key
         * @throws \InvalidArgumentException If the key does not exist and $useDefault is false
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