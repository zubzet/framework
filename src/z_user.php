<?php 
    /**
     * This file holds the user class
     */

    /**
     * The User class holds information about the user
     */
    class User {

        /**
         * @var bool $isLoggedIn Holds if the user is logged in
         */
        public $isLoggedIn = false;

        /**
         * @var int $userId ID of the user in the database.
         * 
         * Null represents an anonymous user
         */
        public $userId = null;

        /**
         * @var int $execUserId ID of the user that is logged in as this user.
         * 
         * Null represents an anonymous user
         */
        public $execUserId = null;

        /**
         * @var mixed[] $fields Holds the dataset from the database of this user
         */
        public $fields = []; //Stores custom per project properties

        /**
         * @var z_framework $booter Holds a reference to the booter
         */
        private $booter;

        /**
         * @var string[] $permissions Array of permissions the user has.
         */
        private $permissions;

        /**
         * @var string|null $sessionToken The token used to authenticate the current session
         */
        private ?string $sessionToken = null;

        /**
         * Creates a new user object
         * @param z_framework $booter The booter object
         */
        public function __construct($booter) {
            $this->booter = $booter;
        }

        /**
         * Checks if the user is registered. Checks by the z_login_token cookie. 
         * The properties $isLoggedIn, $userId, and $execUserId will be set after calling this function.
         */
        public function identify() {
            if (!isset($_COOKIE["z_login_token"]) || empty($_COOKIE["z_login_token"])) {
                return $this->anonymousRequest();
            }

            $tokenResult = $this->booter->getModel("z_login")->validateCookie($_COOKIE["z_login_token"]);
            if(!isset($tokenResult["userId"]) || !isset($tokenResult["userId_exec"])) {
                return $this->anonymousRequest();
            }
            $this->userId = $tokenResult["userId"];
            $this->execUserId = $tokenResult["userId_exec"];
            $this->sessionToken = $tokenResult["token"];

            if ($this->userId !== false) {
                $user = $this->booter->getModel("z_user")->getUserById($this->userId);
                if ($user !== false) {
                    $this->isLoggedIn = true;
                    $this->fields = $user;
                }
            }
        }

        private function anonymousRequest() {
            $this->isLoggedIn = false;
            return;
        }

        /**
         * Checks if a user has a given permission
         * @param string $permission Name of the permission
         * @return bool True when the permission is given
         */
        public function checkPermission($permission): bool {
            return $this->checkPermissionOf($permission, $this->userId);
        }

        public function checkSuperPermission($permission): bool {
            // Check if the user has the permission themselves
            if($this->checkPermissionOf($permission, $this->userId)) {
                return true;
            }

            // Skip checks if there is no exec user
            if($this->userId === $this->execUserId || is_null($this->execUserId)) {
                return false;
            }

            // Otherwise check if the exec user has the permission
            return $this->checkPermissionOf(
                $permission,
                $this->execUserId,
            );
        }

        public function checkPermissionOf($permission, int $userId): bool {
            if (!isset($this->permissions)) {
                $this->permissions = $this->booter->getModel("z_user")->getPermissionsByUserId(
                    $userId,
                );
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

        public function getSessionToken(): ?string {
            return $this->sessionToken;
        }

    }
?>
