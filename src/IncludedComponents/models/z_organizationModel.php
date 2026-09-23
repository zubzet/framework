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

        public function createInvite(Organization $organization, string $email): string {
            $query = $this->dbInsert("z_organization_invite", [
                "organizationId" => $organization->id(),
                "email" => $email,
                "token" => bin2hex(random_bytes(16))
            ]);
            $insertedId = $this->exec($query)->getInsertId();

            return $this->getInviteById($insertedId)["token"];
        }

        public function getInviteById(int $id): ?array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "id" => $id,
                "active" => 1
            ]);

            return $this->exec($query)->resultToLine();
        }

        public function getInvitesByOrganization(Organization $organization): array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "organizationId" => $organization->id(),
                "active" => 1,
            ])->orderDesc("created");

            return $this->exec($query)->resultToArray();
        }

        public function getInvitesByEmail(Organization $organization, string $email): array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "organizationId" => $organization->id(),
                "email" => $email,
                "active" => 1,
            ]);

            return $this->exec($query)->resultToArray();
        }

        // Roles and groups an organization may hand to its members, by `is_org_assignable`
        public function getAssignableRoles(): array {
            $query = $this->dbSelect("*", "z_role")->where([
                "is_org_assignable" => 1,
                "active" => 1,
            ])->orderAsc("name");

            return $this->exec($query)->resultToArray();
        }

        // Which assignable roles the members of an organization hold, one row per user and role
        public function getAssignedRoles(Organization $organization): array {
            $query = $this->dbSelect(["user" => "zur.user", "role" => "zur.role"], ["zur" => "z_user_role"])
                ->innerJoin(["zu" => "z_user"], "zu.id = zur.user")
                ->innerJoin(["zr" => "z_role"], "zr.id = zur.role")
                ->where([
                    "zu.organizationId" => $organization->id(),
                    "zu.active" => 1,
                    "zur.active" => 1,
                    "zr.is_org_assignable" => 1,
                    "zr.active" => 1,
                ]);

            return $this->exec($query)->resultToArray();
        }

        // Accepting and revoking both take the invite out of use
        public function deactivateInvite(int $id): void {
            $query = $this->dbUpdate("z_organization_invite", [
                "active" => 0
            ])->where([
                "id" => $id,
                "active" => 1
            ]);

            $this->exec($query);
        }

        public function getInviteByToken(string $token): ?array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "token" => $token,
                "active" => 1,
            ]);

            return $this->exec($query)->resultToLine();
        }

    }

?>
