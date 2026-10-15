<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\Group;

    class z_organizationModel extends z_model {

        // An organization invite stays valid for seven days
        private const INVITE_LIFETIME = TIMESPAN_DAY_7;

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
            $token = bin2hex(random_bytes(16));

            $query = $this->dbInsert("z_organization_invite", [
                "organizationId" => $organization->id(),
                "email" => $email,
                "token" => $token
            ]);

            $this->exec($query);
            return $token;
        }

        public function getInvite(Organization $organization, int $id): ?array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "id" => $id,
                "organizationId" => $organization->id(),
                "active" => 1
            ]);

            return $this->exec($query)->resultToLine();
        }

        // Only an open invite is returned, an expired one no longer counts
        public function getInviteByToken(string $token): ?array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "token" => $token,
                "active" => 1,
                "created >=" => date("Y-m-d H:i:s", time() - self::INVITE_LIFETIME)
            ]);

            return $this->exec($query)->resultToLine();
        }

        public function getInvitesByOrganization(Organization $organization): array {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "organizationId" => $organization->id(),
                "active" => 1,
                "created >=" => date("Y-m-d H:i:s", time() - self::INVITE_LIFETIME)
            ])->orderDesc("created");

            return array_map(
                fn($invite) => $invite + ["expires_at" => strtotime($invite["created"]) + self::INVITE_LIFETIME],
                $this->exec($query)->resultToArray(),
            );
        }

        public function hasOpenInvite(Organization $organization, string $email): bool {
            $query = $this->dbSelect("*", "z_organization_invite")->where([
                "organizationId" => $organization->id(),
                "email" => $email,
                "active" => 1,
                "created >=" => date("Y-m-d H:i:s", time() - self::INVITE_LIFETIME)
            ]);

            return $this->exec($query)->countResults() > 0;
        }

        // Accepting and revoking both take the invite out of use, only within its organization
        public function deactivateInvite(Organization $organization, int $id): void {
            $query = $this->dbUpdate("z_organization_invite", [
                "active" => 0
            ])->where([
                "id" => $id,
                "organizationId" => $organization->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

        // Roles and groups an organization may hand to its members, by `is_org_assignable`
        public function getAssignableRoles(): array {
            $query = $this->dbSelect("*", "z_role")->where([
                "is_org_assignable" => 1,
                "active" => 1
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
                    "zr.active" => 1
                ]);

            return $this->exec($query)->resultToArray();
        }

    }

?>
