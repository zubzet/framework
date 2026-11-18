<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\Group;

    class z_organizationModel extends z_model {

        // An organization invite stays valid for seven days
        private const INVITE_LIFETIME = TIMESPAN_DAY_7;

        public function create(?string $name, ?Group $group = null, ?int $maxCapacity = null): ?array {
            $insertValues = [
                "name" => $name,
                "max_capacity" => $maxCapacity
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

        public function updateMaxCapacity(Organization $organization, ?int $maxCapacity): void {
            $query = $this->dbUpdate("z_organization", [
                "max_capacity" => $maxCapacity
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

        public function createInvite(Organization $organization, string $email, ?int $roleId = null): array {
            $token = bin2hex(random_bytes(16));

            $query = $this->dbInsert("z_organization_invite", [
                "organizationId" => $organization->id(),
                "email" => $email,
                "roleId" => $roleId,
                "token" => $token
            ]);

            $this->exec($query);
            return $this->getInviteByToken($token);
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

        // Active members plus open invites fill the capacity, the invite being accepted does not count against itself
        public function isFull(Organization $organization, int $exceptInviteId = 0): bool {
            $maxCapacity = $organization->maxCapacity();
            if(is_null($maxCapacity)) return false;

            $members = count($organization->getUsers());
            $invites = count(array_filter(
                $this->getInvitesByOrganization($organization),
                fn($invite) => $invite["id"] != $exceptInviteId,
            ));

            return $members + $invites >= $maxCapacity;
        }

        // A deleted member keeps its organization until removed, updateOrganization() only handles active accounts
        public function removeDeletedMember(Organization $organization, int $userId): bool {
            $where = [
                "id" => $userId,
                "organizationId" => $organization->id(),
                "active" => 0
            ];

            if(0 === $this->exec($this->dbSelect("id", "z_user")->where($where))->countResults()) return false;

            $this->exec($this->dbUpdate("z_user", ["organizationId" => null])->where($where));
            return true;
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
            $query = $this->dbSelect(["id", "name"], "z_role")->where([
                "is_org_assignable" => 1,
                "active" => 1
            ])->orderAsc("name");

            return $this->exec($query)->resultToArray();
        }

        // The members of an organization, deleted ones only with $showInactive, with the ids of the assignable roles each one holds
        public function getMembers(Organization $organization, bool $showInactive = false): array {
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

            $heldRoleIds = [];
            foreach($this->exec($query)->resultToArray() as $assignment) {
                $heldRoleIds[$assignment["user"]][] = $assignment["role"];
            }

            $query = $this->dbSelect(["id", "email", "active"], "z_user")->where([
                "organizationId" => $organization->id()
            ])->orderAsc("id");

            if(!$showInactive) {
                $query->where([
                    "active" => 1
                ]);
            }

            return array_map(fn($member) => [
                "id" => (int) $member["id"],
                "email" => $member["email"],
                "deleted" => !$member["active"],
                "roles" => $heldRoleIds[$member["id"]] ?? [],
            ], $this->exec($query)->resultToArray());
        }

    }

?>
