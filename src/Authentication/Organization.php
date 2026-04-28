<?php

    namespace ZubZet\Framework\Authentication;

    use ZubZet\Framework\Authentication\Permission\User;

    class Organization extends AuthenticationObject {

        use RetrievalTrait;
        use HandleTrait;

        public static string $dbTable = "z_organization";
        public static array $dbExpression = [];

        public function __construct(array $data) {
            parent::__construct($data);
            $this->loadObject($data);
        }

        public function loadObject(array $data) {
            $this->data = $data;
            $this->setField("users", null);
        }

        public static function add(?string $name): Organization {
            $organizationData = model("z_organization")->create($name);
            return new Organization($organizationData);
        }

        /**
         * Get the organization a given user belongs to.
         *
         * Convenience pass-through to {@see User::organization()} so callers can use the
         * symmetric `Organization::byUser($user)` style alongside other `by*` retrievers.
         *
         * @param User $user The user to look up
         * @return Organization|null The organization or null if the user has none
         */
        public static function byUser(User $user): ?Organization {
            return $user->organization();
        }

        /**
         * Get all active organizations matching the given name.
         *
         * Organization names are not unique, so this returns an array — possibly empty,
         * possibly containing multiple organizations sharing the same name.
         *
         * @param string $name The name to match exactly
         * @return Organization[] All active organizations with the given name
         */
        public static function byName(string $name): array {
            $organizations = [];
            $organizationDataList = model("z_organization")->byName($name);

            foreach($organizationDataList as $organizationData) {
                $organizations[] = new Organization($organizationData);
            }

            return $organizations;
        }

        public function updateName(string $name): void {
            model("z_organization")->updateName($this, $name);

            $this->setField("name", $name);
        }

        public function name(): string {
            return $this->getField("name");
        }

        public function getUsers(): array {
            if(is_null($this->getField("users"))) $this->refreshUsers();

            return $this->getField("users");
        }

        public function refreshUsers(): void {
            $users = model("z_user")->getUsersByOrganization($this);
            $this->setField("users", $users);
        }

        public function remove(): void {
            model("z_organization")->remove($this);
        }
    }

?>
