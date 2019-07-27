<?php 
    /**
     * This file holds the user class
     */

    /**
     * User class can hold user information
     */
    class User {

        /**
         * @var bool $isLoggedIn Hold is the user is logged
         */
        public $isLoggedIn = false;

        /**
         * @var int $userId Id of the user in the database.
         * 
         * Null is equal to anonymous
         */
        public $userId = null;

        /**
         * @var int $execUserId Id of the user that is logged in as this user.
         * 
         * Null is equal to anonymous
         */
        public $execUserId = null;

        /**
         * @var array $language Holds information about the language of the user.
         * 
         * Array with the keys "id" and "value".
         */
        public $language = ["id" => 0, "value" => "EN"]; //ToDo: Insert default language here

        /**
         * @var any[] $fields Holds the dataset from the database of this user
         */
        public $fields = []; //Stores custom per project properties

        /**
         * @var z_framework $booter Holds a reference to the booter
         */
        private $booter;

        /**
         * @var string[] $permissions. Array of permissions the user has.
         */
        private $permissions;

        /**
         * Creates a new user object
         * @param z_framework $booter The booter object
         */
        public function __construct($booter) {
            $this->booter = $booter;
        }

        /**
         * Checks if the user is someone registered. Checks by the z_login_token cookie. 
         * The property $isLoggedIn and $userId and $execUserId will be set after calling this function.
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

        /**
         * Checks if a user has a given permission
         * @param string $permission Name of the permission
         * @return bool True when the permission is given
         */
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