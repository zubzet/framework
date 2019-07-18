<?php 
    class User {
        public $isLoggedIn = false;
        public $userId = null; //Null => anonymous
        public $execUserId = null;
        public $language = ["id" => 0, "value" => "EN"]; //ToDo: Insert default language here

        public $fields = []; //Stores custom per project properties

        private $booter;
        private $permissions;

        public function __construct($booter) {
            $this->booter = $booter;
        }

        /**
         * Checks if the user is someone registered
         */
        public function identify() {
            if (!isset($_COOKIE["z_login_token"]) || empty($_COOKIE["z_login_token"])) {
                $this->isLoggedIn = false;
                return;
            }

            $tokenResult = $this->booter->getModel("z_login")->validateCookie($_COOKIE["z_login_token"]);
            $this->userId = $tokenResult["userId"];
            $this->execUserId = $tokenResult["userId_exec"];

            if ($this->userId !== false) {
                $user = $this->booter->getModel("z_user")->getUserById($this->userId);
                if ($user !== false) {
                    $this->isLoggedIn = true;
                    $this->language["id"] = $user["languageId"];
                    $this->language["value"] = $this->booter->getModel("z_general")->getLanguageById($this->language["id"])["value"];
                    $this->fields = $user;
                }
            }

        }

        public function checkPermission($permission) {
            if (!isset($this->permissions)) {
                $this->permissions = $this->booter->getModel("z_user")->getPermissionsByUserId($this->userId);
            }
            $parts = explode(".", $permission);

            $perm = "";
            $toCheck = ["*.*", $permission];

            foreach ($parts as $part) {
                $perm .= $part . ".";
                $toCheck[] = $perm . "*";
            }
            
            foreach ($toCheck as $check) {
                if (in_array($check, $this->permissions)) return true;
            }
            return false;
        }

    }
?>