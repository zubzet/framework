<?php

    namespace ZubZet\Framework\Authentication;

    use ZubZet\Framework\Authentication\Permission\User;

    class Organisation extends AuthenticationObject {

        use RetrievalTrait;
        use HandleTrait;

        public static string $dbTable = "z_organisation";
        public static array $dbExpression = [];

        public function __construct(array $data) {
            parent::__construct($data);
            $this->loadObject($data);
        }

        public function loadObject(array $data) {
            $this->data = $data;
            $this->setField("users", null);
        }

        public static function add(?string $name): Organisation {
            $organisationData = model("z_organisation")->create($name);
            return new Organisation($organisationData);
        }

        public static function byUser(User $user): ?Organisation {
            return $user->organisation();
        }

        public static function byName(string $name): ?Organisation {
            $organisationData = model("z_organisation")->byName($name);
            if(!$organisationData) return null;
            return new Organisation($organisationData);
        }

        public function update(string $name): void {
            model("z_organisation")->update($this, $name);

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
            $users = model("z_permission")->getUsersByOrganisation($this);
            $this->setField("users", $users);
        }

        public function remove(): void {
            model("z_organisation")->remove($this);
        }
    }

?>