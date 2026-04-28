<?php

    namespace ZubZet\Framework\Authentication;

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
         * @var int $orgId ID of the organization the user belongs to.
         *
         * Null represents an user that does not belong to an organization, or an anonymous user.
         *
         * Named `$orgId` rather than `$organizationId` (which is the column name) because this
         * property is read frequently in application code and the shorter name keeps call sites
         * tidy. The corresponding DB column is still `organizationId`.
         */
        public $orgId = null;

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
         * @var string[int] $permissionsByUserCache Array of permissions the user has, cached by user ID.
         */
        private $permissionsByUserCache = [];

        /**
         * @var string|null $sessionToken The token used to authenticate the current session
         */
        private ?string $sessionToken = null;

        public function __construct() {
            $this->identify();
        }

        /**
         * Checks if the user is registered. Checks by the z_login_token cookie. 
         * The properties $isLoggedIn, $userId, and $execUserId will be set after calling this function.
         */
        public function identify() {
            if(empty(request()->getCookie("z_login_token"))) {
                return $this->anonymousRequest();
            }

            $session = Session::byToken(request()->getCookie("z_login_token"));
            if(is_null($session) ||
                !model("z_login")->validateSession($session) ||
                is_null($session->userId()) ||
                is_null($session->userIdExec()))
            {
                return $this->anonymousRequest();
            }
            $this->userId = $session->userId();
            $this->execUserId = $session->userIdExec();
            $this->sessionToken = $session->token();

            if (!is_null($this->userId)) {
                $user = model("z_user")->getUserById($this->userId);
                if ($user !== false) {
                    $this->orgId = $user["organizationId"];
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
            if(!$this->isLoggedIn) return false;
            return $this->checkPermissionOf($permission, $this->userId);
        }

        public function checkSuperPermission($permission): bool {
            if(!$this->isLoggedIn) return false;

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
            $userPermissions = &$this->permissionsByUserCache[$userId] ?? null;
            if(!isset($userPermissions)) {
                $userPermissions = model("z_user")->getPermissionsByUserId($userId);
            }

            $parts = explode(".", $permission);

            $perm = "";
            $toCheck = ["*.*", $permission];

            foreach ($parts as $part) {
                $perm .= $part . ".";
                $toCheck[] = $perm . "*";
            }

            foreach ($toCheck as $check) {
                if (in_array($check, $userPermissions)) return true;
            }
            return false;
        }

        public function getSessionToken(): ?string {
            return $this->sessionToken;
        }

    }
?>
