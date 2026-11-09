<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\Group;
    use ZubZet\Framework\Authentication\Permission\Role;
    use ZubZet\Framework\Authentication\Permission\User;

    class OrganizationController extends z_controller {

        /**
         *
         * @var Organization Getters
         *
         */
        public function action_byId(Request $req, Response $res): void {
            $organization = Organization::byId(500);
            $this->echoOrganization($organization, false);
        }

        public function action_byIdInactive(Request $req, Response $res): void {
            $organization = Organization::byId(501);
            $this->echoOrganization($organization, false);
        }

        public function action_byName(Request $req, Response $res): void {
            $organizations = Organization::byName("org_byName_Shared");
            $this->echoOrganizations($organizations, false);
        }

        public function action_byNameInactive(Request $req, Response $res): void {
            $organizations = Organization::byName("org_byName_Inactive");
            $this->echoOrganizations($organizations, false);
        }

        public function action_byUser(Request $req, Response $res): void {
            $organization = Organization::byUser(User::byId(500));
            $this->echoOrganization($organization, false);
        }


        /**
         *
         * @var Organization Interactions
         *
         */
        public function action_add(Request $req, Response $res): void {
            $organization = Organization::add("org_add_NewOrganization");
            $createdOrganizationDirect = $this->getOrganization($organization, false);

            $organization = Organization::byId($organization->id());
            $createdOrganizationGet = $this->getOrganization($organization, false);

            echo(json_encode([
                "createdOrganizationDirect" => $createdOrganizationDirect,
                "createdOrganizationGet" => $createdOrganizationGet
            ]));
        }

        public function action_updateName(Request $req, Response $res): void {
            $organization = Organization::byId(507);
            $organization->updateName("org_updateName_NewName");

            $this->echoOrganization(Organization::byId(507), false);
        }

        public function action_remove(Request $req, Response $res): void {
            $organization = Organization::byId(508);
            $organization->remove();

            $organizationAfterRemoval = Organization::byId(508);
            $this->echoOrganization($organizationAfterRemoval, false);
        }

        public function action_getUsers(Request $req, Response $res): void {
            $organization = Organization::byId(506);
            $this->echoOrganization($organization, true);
        }


        /**
         *
         * @var User Interactions with Organization
         *
         */
        public function action_userOrganization(Request $req, Response $res): void {
            $user = User::byId(500);
            $organization = $user->organization();

            echo(json_encode([
                "id" => $organization->id(),
                "name" => $organization->name()
            ]));
        }

        public function action_userOrganizationNull(Request $req, Response $res): void {
            $user = User::byId(504);
            $organization = $user->organization();

            echo(json_encode([
                "found" => $organization !== null
            ]));
        }

        public function action_userUpdateOrganizationAssign(Request $req, Response $res): void {
            $user = User::byId(504);
            $user->updateOrganization(Organization::byId(509));

            $reloaded = User::byId(504);
            $organization = $reloaded->organization();

            echo(json_encode([
                "id" => $organization->id(),
                "name" => $organization->name()
            ]));
        }

        public function action_userUpdateOrganizationChange(Request $req, Response $res): void {
            $user = User::byId(505);
            $user->updateOrganization(Organization::byId(510));

            $reloaded = User::byId(505);
            $organization = $reloaded->organization();

            echo(json_encode([
                "id" => $organization->id(),
                "name" => $organization->name()
            ]));
        }

        public function action_userUpdateOrganizationUnset(Request $req, Response $res): void {
            $user = User::byId(505);
            $user->updateOrganization(null);

            $reloaded = User::byId(505);
            $organization = $reloaded->organization();

            echo(json_encode([
                "found" => $organization !== null
            ]));
        }

        public function action_userByOrganization(Request $req, Response $res): void {
            $users = User::byOrganization(Organization::byId(506));

            $result = [];
            foreach($users as $user) {
                $result[] = [
                    "id" => $user->id(),
                    "email" => $user->email()
                ];
            }

            echo(json_encode($result));
        }

        // Org 511 has no members. The populated-foreach mapping is already
        // covered by action_userByOrganization above; here we only need to
        // demonstrate that User::byOrganization returns an empty list - no
        // need to iterate it (and create coverage noise on a body that, by
        // definition, can never run).
        public function action_userByOrganizationEmpty(Request $req, Response $res): void {
            $users = User::byOrganization(Organization::byId(511));
            echo(json_encode($users));
        }


        /**
         *
         * @var Organization / User Membership
         *
         */
        // /organization/hasMember/<organizationId>/<userId>
        public function action_hasMember(Request $req, Response $res): void {
            $organization = Organization::byId((int) $req->getParameters(0, 1));
            echo(json_encode($organization->hasMember(User::byId((int) $req->getParameters(1, 1)))));
        }

        // /organization/isMemberOf/<userId>/<organizationId>
        public function action_isMemberOf(Request $req, Response $res): void {
            $user = User::byId((int) $req->getParameters(0, 1));
            echo(json_encode($user->isMemberOf(Organization::byId((int) $req->getParameters(1, 1)))));
        }


        /**
         *
         * @var Request requireOrganization
         *
         * Each shape gets its own probe: output before the 403 page sends the headers and
         * http_response_code() then fails. The boolResult probe prints allowed/denied, the
         * default probe lets "passed" through only on access and shows the login or 403 page otherwise.
         *
         */
        public function action_requireLogin(Request $req, Response $res): void {
            $res->loginAs((int) $req->getParameters(0, 1));
        }

        public function action_requireBool(Request $req, Response $res): void {
            echo $req->requireOrganization(true) ? "allowed" : "denied";
        }

        public function action_require(Request $req, Response $res): void {
            $req->requireOrganization();
            echo "passed";
        }


        /**
         *
         * @var Organization Permissions, Roles and Groups
         *
         */
        public function action_hasAccess(Request $req, Response $res): void {
            $organization = Organization::byId(520);

            echo(json_encode([
                "all" => $organization->hasAccessAll("orgperm.direct.read", "orgperm.direct.write"),
                "allWithMissing" => $organization->hasAccessAll("orgperm.direct.read", "orgperm.direct.missing"),
                "anyOf" => $organization->hasAccessAnyOf("orgperm.direct.missing", "orgperm.direct.write"),
                "anyOfInactive" => $organization->hasAccessAnyOf("orgperm.direct.missing", "orgperm.direct.inactive"),
                "wildcard" => $organization->hasAccessAll("orgperm.wild.deep.name"),
                "wildcardOutside" => $organization->hasAccessAnyOf("orgperm.other.name")
            ]));
        }

        public function action_getPermissions(Request $req, Response $res): void {
            echo(json_encode(Organization::byId(521)->getPermissions()));
        }

        public function action_getRoles(Request $req, Response $res): void {
            $organization = Organization::byId(522);

            echo(json_encode([
                "roles" => $this->getNames($organization->getRoles()),
                "groups" => $this->getNames($organization->getGroups())
            ]));
        }

        public function action_byOrganization(Request $req, Response $res): void {
            $organization = Organization::byId(522);

            echo(json_encode([
                "roles" => $this->getNames(Role::byOrganization($organization)),
                "groups" => $this->getNames(Group::byOrganization($organization))
            ]));
        }

        public function action_byRole(Request $req, Response $res): void {
            echo(json_encode([
                "byRole" => $this->getNames(Organization::byRole(Role::byId(157))),
                "byGroup" => $this->getNames(Organization::byGroup(Group::byId(159)))
            ]));
        }

        public function action_byAccessToAll(Request $req, Response $res): void {
            $organizations = Organization::byAccessToAll("orgperm.byAccess.1", "orgperm.byAccess.2");
            echo(json_encode($this->getNames($organizations)));
        }

        public function action_byAccessToAnyOf(Request $req, Response $res): void {
            $organizations = Organization::byAccessToAnyOf("orgperm.byAccess.1", "orgperm.byAccess.2");
            echo(json_encode($this->getNames($organizations)));
        }


        /**
         *
         * @var Members inherit what their Organization holds
         *
         */
        public function action_inheritDirect(Request $req, Response $res): void {
            $member = User::byId(570);
            $none = User::byId(571);
            $other = User::byId(572);

            echo(json_encode([
                "memberAll" => $member->hasAccessAll("orgperm.direct.read", "orgperm.direct.write"),
                "memberAllWithMissing" => $member->hasAccessAll("orgperm.direct.read", "orgperm.direct.missing"),
                "memberAnyOf" => $member->hasAccessAnyOf("orgperm.direct.missing", "orgperm.direct.write"),
                "memberAnyOfInactive" => $member->hasAccessAnyOf("orgperm.direct.inactive"),
                "memberWildcard" => $member->hasAccessAll("orgperm.wild.deep.name"),
                "memberPermissions" => $member->getPermissions(),
                "noneAll" => $none->hasAccessAll("orgperm.direct.write"),
                "noneAnyOf" => $none->hasAccessAnyOf("orgperm.direct.write", "orgperm.wild.deep.name"),
                "otherAnyOf" => $other->hasAccessAnyOf("orgperm.direct.write", "orgperm.wild.deep.name"),
                "otherPermissions" => $other->getPermissions()
            ]));
        }

        public function action_inheritRoleGroup(Request $req, Response $res): void {
            $member = User::byId(598);
            $none = User::byId(599);

            echo(json_encode([
                "memberAll" => $member->hasAccessAll("orgperm.viaRole.perm", "orgperm.viaGroup.perm"),
                "memberPermissions" => $member->getPermissions(),
                "noneAnyOf" => $none->hasAccessAnyOf("orgperm.viaRole.perm", "orgperm.viaGroup.perm"),
                "nonePermissions" => $none->getPermissions()
            ]));
        }

        public function action_userByRoleGroup(Request $req, Response $res): void {
            $user = User::byId(580);

            echo(json_encode([
                "roles" => $this->getNames(Role::byUser($user)),
                "groups" => $this->getNames(Group::byUser($user)),
                "userRoles" => $this->getNames($user->getRoles()),
                "userGroups" => $this->getNames($user->getGroups())
            ]));
        }

        public function action_usersByRoleGroup(Request $req, Response $res): void {
            echo(json_encode([
                "byRole" => $this->getNames(User::byRole(Role::byId(170)), "email"),
                "byGroup" => $this->getNames(User::byGroup(Group::byId(171)), "email"),
                "roleGetUsers" => $this->getNames(Role::byId(170)->getUsers(), "email"),
                "groupGetUsers" => $this->getNames(Group::byId(171)->getUsers(), "email"),
                "otherRole" => $this->getNames(Role::byId(172)->getUsers(), "email")
            ]));
        }

        public function action_userByAccessToAll(Request $req, Response $res): void {
            $users = User::byAccessToAll("orgperm.userAccess.1", "orgperm.userAccess.2");
            echo(json_encode($this->getNames($users, "email")));
        }

        public function action_userByAccessToAnyOf(Request $req, Response $res): void {
            $users = User::byAccessToAnyOf("orgperm.userAccess.1", "orgperm.userAccess.2");
            echo(json_encode($this->getNames($users, "email")));
        }

        public function action_inactiveOrganization(Request $req, Response $res): void {
            $member = User::byId(592);

            echo(json_encode([
                "permissions" => $member->getPermissions(),
                "anyOf" => $member->hasAccessAnyOf("orgperm.inactiveOrganization.direct", "orgperm.inactiveOrganization.role"),
                "roles" => $this->getNames(Role::byUser($member)),
                "users" => $this->getNames(User::byRole(Role::byId(176)), "email"),
                "organizations" => $this->getNames(Organization::byRole(Role::byId(176)))
            ]));
        }

        public function action_noOrganization(Request $req, Response $res): void {
            $none = User::byId(596);
            $member = User::byId(597);

            echo(json_encode([
                "nonePermissions" => $none->getPermissions(),
                "noneRoles" => $this->getNames(Role::byUser($none)),
                "noneAll" => $none->hasAccessAll("orgperm.regression.direct", "orgperm.regression.role"),
                "memberPermissions" => $member->getPermissions(),
                "memberRoles" => $this->getNames(Role::byUser($member)),
                "byRole" => $this->getNames(User::byRole(Role::byId(181)), "email")
            ]));
        }


        /**
         *
         * @var Changing what an Organization holds
         *
         */
        public function action_mutatePermissions(Request $req, Response $res): void {
            $organization = Organization::byId(543);
            $member = User::byId(587);

            $before = $member->hasAccessAnyOf("orgperm.mutatePermissions.perm");

            $organization->permissionsAdd("orgperm.mutatePermissions.perm", "orgperm.mutatePermissions.other");
            $added = [
                "organization" => $organization->getPermissions(),
                "member" => $member->getPermissions()
            ];

            $organization->permissionsAdd("*.*");
            $wildcard = $member->hasAccessAll("orgperm.mutatePermissions.anything");
            $organization->permissionsRemove("*.*");
            $wildcardRemoved = $member->hasAccessAll("orgperm.mutatePermissions.anything");

            $organization->permissionsRemove("orgperm.mutatePermissions.perm");
            $removed = [
                "organization" => $organization->getPermissions(),
                "member" => $member->getPermissions(),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.mutatePermissions.perm")
            ];

            echo(json_encode([
                "before" => $before,
                "added" => $added,
                "wildcard" => $wildcard,
                "wildcardRemoved" => $wildcardRemoved,
                "removed" => $removed
            ]));
        }

        public function action_mutateRoles(Request $req, Response $res): void {
            $organization = Organization::byId(544);
            $member = User::byId(588);
            $role = Role::byId(173);

            $before = $member->hasAccessAnyOf("orgperm.mutateRoles.perm");

            $organization->rolesAdd($role);
            $added = [
                "organization" => $this->getNames($organization->getRoles()),
                "memberRoles" => $this->getNames(Role::byUser($member)),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.mutateRoles.perm")
            ];

            $organization->rolesRemove($role);
            $removed = [
                "organization" => $this->getNames($organization->getRoles()),
                "memberRoles" => $this->getNames(Role::byUser($member)),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.mutateRoles.perm")
            ];

            echo(json_encode([
                "before" => $before,
                "added" => $added,
                "removed" => $removed
            ]));
        }

        public function action_mutateGroups(Request $req, Response $res): void {
            $organization = Organization::byId(545);
            $member = User::byId(589);
            $group = Group::byId(174);

            $before = $member->hasAccessAnyOf("orgperm.mutateGroups.perm");

            $organization->groupsAdd($group);
            $added = [
                "organization" => $this->getNames($organization->getGroups()),
                "memberGroups" => $this->getNames(Group::byUser($member)),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.mutateGroups.perm")
            ];

            $organization->groupsRemove($group);
            $removed = [
                "organization" => $this->getNames($organization->getGroups()),
                "memberGroups" => $this->getNames(Group::byUser($member)),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.mutateGroups.perm")
            ];

            echo(json_encode([
                "before" => $before,
                "added" => $added,
                "removed" => $removed
            ]));
        }

        public function action_userMoveOrganization(Request $req, Response $res): void {
            $before = User::byId(590)->getPermissions();

            User::byId(590)->updateOrganization(Organization::byId(547));
            $moved = User::byId(590)->getPermissions();

            User::byId(590)->updateOrganization(null);
            $unset = User::byId(590)->getPermissions();

            echo(json_encode([
                "before" => $before,
                "moved" => $moved,
                "unset" => $unset
            ]));
        }

        public function action_removeOrganization(Request $req, Response $res): void {
            $member = User::byId(591);
            $before = [
                "permissions" => $member->getPermissions(),
                "roles" => $this->getNames(Role::byUser($member))
            ];

            Organization::byId(548)->remove();

            $member = User::byId(591);
            echo(json_encode([
                "before" => $before,
                "after" => [
                    "permissions" => $member->getPermissions(),
                    "roles" => $this->getNames(Role::byUser($member))
                ]
            ]));
        }

        public function action_roleRemoved(Request $req, Response $res): void {
            $organization = Organization::byId(550);
            $member = User::byId(593);

            $before = [
                "organization" => $this->getNames($organization->getRoles()),
                "memberAccess" => $member->hasAccessAnyOf("orgperm.roleRemoved.perm")
            ];

            Role::byId(177)->remove();

            echo(json_encode([
                "before" => $before,
                "after" => [
                    "organization" => $this->getNames($organization->getRoles()),
                    "memberRoles" => $this->getNames(Role::byUser($member)),
                    "memberAccess" => $member->hasAccessAnyOf("orgperm.roleRemoved.perm")
                ]
            ]));
        }


        /**
         *
         * @var Request checkPermission, Authentication\User::checkPermissionOf
         *
         */
        public function action_legacyCheck(Request $req, Response $res): void {
            echo(json_encode([
                "direct" => $req->checkPermission("orgperm.legacy.direct", true),
                "role" => $req->checkPermission("orgperm.legacy.role", true),
                "missing" => $req->checkPermission("orgperm.legacy.missing", true),
                "ofMember" => user()->checkPermissionOf("orgperm.legacy.role", 594),
                "ofNone" => user()->checkPermissionOf("orgperm.legacy.role", 595)
            ]));
        }


        /**
         *
         * @var Organization <-> Group Link
         *
         */
        public function action_addWithGroup(Request $req, Response $res): void {
            $organization = Organization::add("org_addWithGroup_NewOrganization", true);
            $group = $organization->getGroup();

            echo(json_encode([
                "organization" => $this->getOrganization($organization, false, true),
                "groupHasOrgNameSuffix" => $group !== null && $group->name() === "org_addWithGroup_NewOrganization_Group"
            ]));
        }

        public function action_addWithoutGroup(Request $req, Response $res): void {
            $organization = Organization::add("org_addWithoutGroup_NewOrganization");
            $this->echoOrganization($organization, false, true);
        }

        public function action_getGroup(Request $req, Response $res): void {
            $organization = Organization::byId(512);
            $this->echoOrganization($organization, false, true);
        }

        public function action_getGroupNull(Request $req, Response $res): void {
            $organization = Organization::byId(513);
            $this->echoOrganization($organization, false, true);
        }


        /**
         *
         * @var User::updateOrganization Group Sync
         *
         */
        public function action_userUpdateOrganizationGroupSyncAssign(Request $req, Response $res): void {
            $user = User::byId(550);
            $user->updateOrganization(Organization::byId(514));

            $reloaded = User::byId(550);
            echo(json_encode([
                "groups" => $this->getGroupsOfUser($reloaded)
            ]));
        }

        public function action_userUpdateOrganizationGroupSyncChange(Request $req, Response $res): void {
            $user = User::byId(551);
            $user->updateOrganization(Organization::byId(515));

            $reloaded = User::byId(551);
            echo(json_encode([
                "groups" => $this->getGroupsOfUser($reloaded)
            ]));
        }

        public function action_userUpdateOrganizationGroupSyncUnset(Request $req, Response $res): void {
            $user = User::byId(552);
            $user->updateOrganization(null);

            $reloaded = User::byId(552);
            echo(json_encode([
                "groups" => $this->getGroupsOfUser($reloaded)
            ]));
        }


        /**
         *
         * @var Organization Helper Functions
         *
         */
        private function echoOrganization(?Organization $organization, bool $includeUsers, bool $includeGroup = false): void {
            echo(json_encode($this->getOrganization($organization, $includeUsers, $includeGroup)));
        }

        private function echoOrganizations(array $organizations, bool $includeUsers): void {
            $result = [];
            foreach($organizations as $organization) {
                $result[] = $this->getOrganization($organization, $includeUsers);
            }
            echo(json_encode($result));
        }

        private function getOrganization(?Organization $organization, bool $includeUsers, bool $includeGroup = false): array {
            if($organization === null) {
                return [
                    "found" => false
                ];
            }

            $organizationData = [
                "id" => $organization->id(),
                "name" => $organization->name(),
            ];

            if($includeUsers) {
                $users = $organization->getUsers();

                $organizationData["users"] = [];
                foreach($users as $user) {
                    $organizationData["users"][] = [
                        "id" => $user->id(),
                        "email" => $user->email()
                    ];
                }
            }

            if($includeGroup) {
                $group = $organization->getGroup();
                $organizationData["group"] = $group === null ? null : [
                    "id" => $group->id(),
                    "name" => $group->name()
                ];
            }

            return $organizationData;
        }

        private function getNames(array $objects, string $method = "name"): array {
            return array_map(fn($object) => $object->$method(), $objects);
        }

        private function getGroupsOfUser(User $user): array {
            $groups = [];
            foreach($user->getGroups() as $group) {
                $groups[] = [
                    "id" => $group->id(),
                    "name" => $group->name()
                ];
            }
            return $groups;
        }

    }

?>
