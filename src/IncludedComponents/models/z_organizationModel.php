<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\Group;

    class z_organizationModel extends z_model {

        public function create(?string $name, ?Group $group = null): ?array {
            $insertValues = [
                "name" => $name
            ];

            if(!is_null($group)) {
                $insertValues["groupId"] = $group->id();
            }

            $query = $this->dbInsert("z_organization", $insertValues);
            $insertedId = $this->exec($query)->getInsertId();

            return $this->byId($insertedId);
        }

        public function updateName(Organization $organization, string $name): void {
            $query = $this->dbUpdate("z_organization", [
                "name" => $name
            ])->where([
                "id" => $organization->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

        public function byName(string $name): array {
            $query = $this->dbSelect("*", ["zo" => "z_organization"])->where([
                "zo.name" => $name,
                "zo.active" => 1
            ]);

            return $this->exec($query)->resultToArray();
        }

        public function byId(int $id): ?array {
            $query = $this->dbSelect("*", ["zo" => "z_organization"])->where([
                "zo.id" => $id,
                "zo.active" => 1
            ]);

            return $this->exec($query)->resultToLine();
        }

        public function remove(Organization $organization): void {
            $query = $this->dbUpdate("z_organization", [
                "active" => 0
            ])->where([
                "id" => $organization->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

    }

?>
