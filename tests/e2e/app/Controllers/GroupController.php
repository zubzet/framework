<?php

    use ZubZet\Framework\Authentication\Permission\Group;
    use ZubZet\Framework\Authentication\Permission\Role;
    use ZubZet\Framework\Authentication\Permission\User;

    class GroupController extends z_controller {

        /**
         *
         * @var Group Getters
         *
         */
        public function action_byId(Request $req, Response $res): void {
            $group = Group::byId(300);
            $this->echoGroup($group, false, false);
        }

        public function action_byIdInactive(Request $req, Response $res): void {
            $group = Group::byId(301);
            $this->echoGroup($group, false, false);
        }

        public function action_byIds(Request $req, Response $res): void {
            $groups = Group::byIds(302, 303, 304);
            $this->echoGroups($groups, false, false);
        }

        public function action_byName(Request $req, Response $res): void {
            $group = Group::byName("group_byName_Active");
            $this->echoGroup($group, false, false);
        }

        public function action_byNameInactive(Request $req, Response $res): void {
            $group = Group::byName("group_byName_Inactive");
            $this->echoGroup($group, false, false);
        }

        public function action_byUser(Request $req, Response $res): void {
            $groups = Group::byUser(User::byId(300));
            $this->echoGroups($groups, false, false);
        }

        public function action_byAccessToAll(Request $req, Response $res): void {
            $groups = Group::byAccessToAll("group_byAccessToAll.1", "group_byAccessToAll.2");
            $this->echoGroups($groups, false, false);
        }

        public function action_byAccessToAnyOf(Request $req, Response $res): void {
            $groups = Group::byAccessToAnyOf("group_byAccessToAnyOf.1", "group_byAccessToAnyOf.2");
            $this->echoGroups($groups, false, false);
        }


        /**
         *
         * @var Group Interactions
         *
         */
        public function action_remove(Request $req, Response $res): void {
            $group = Group::byId(309);
            $group->remove();

            $groupAfterDeletion = Group::byId(309);
            $this->echoGroup($groupAfterDeletion, false, false);
        }

        public function action_removeInteraction(Request $req, Response $res): void {
            $group = Group::byId(310);
            $group->remove();

            try {
                $group->name();
            } catch(Exception $e) {
                $this->echoGroup(null, false, false);
            }
        }

        public function action_update(Request $req, Response $res): void {
            $group = Group::byId(311);
            $group->update("group_update_NewName");

            $this->echoGroup(Group::byId(311), false, false);
        }

        public function action_refresh(Request $req, Response $res): void {
            $group = Group::byId(312);

            $group->update("group_refresh_UpdatedName");
            $group->refresh();
            $this->echoGroup($group, false, false);
        }

        public function action_getPermissions(Request $req, Response $res): void {
            $group = Group::byId(313);

            $this->echoGroup($group, true, false);
        }

        public function action_getUsers(Request $req, Response $res): void {
            $group = Group::byId(314);

            $this->echoGroup($group, false, true);
        }

        public function action_add(Request $req, Response $res): void {
            $group = Group::add("group_add_NewGroup");
            $createdGroupDirect = $this->getGroup($group, false, false);

            $group = Group::byId($group->id());
            $createdGroupGet = $this->getGroup($group, false, false);

            echo(json_encode([
                "createdGroupDirect" => $createdGroupDirect,
                "createdGroupGet" => $createdGroupGet
            ]));
        }

        public function action_hasAccessToAll(Request $req, Response $res): void {
            echo(json_encode([
                "hasAccessToAll_15" => Group::byId(315)->hasAccessAll("group_hasAccessToAll.1", "group_hasAccessToAll.2"),
                "hasAccessToAll_16" => Group::byId(316)->hasAccessAll("group_hasAccessToAll.1", "group_hasAccessToAll.2"),
            ]));
        }

        public function action_hasAccessToAnyOf(Request $req, Response $res): void {
            echo(json_encode([
                "hasAccessToAnyOf_17" => Group::byId(317)->hasAccessAnyOf("group_hasAccessToAnyOf.1", "group_hasAccessToAnyOf.2"),
                "hasAccessToAnyOf_18" => Group::byId(318)->hasAccessAnyOf("group_hasAccessToAnyOf.1", "group_hasAccessToAnyOf.2"),
            ]));
        }

        /**
         * Isolation: a Group ID must not be returned by Role::byId
         * and a Role ID must not be returned by Group::byId
         */
        public function action_isolation(Request $req, Response $res): void {
            $groupAsRole = Role::byId(300); // is_group=1 → must not be found by Role
            $roleAsGroup = Group::byId(200); // is_group=0 → must not be found by Group

            echo(json_encode([
                "groupFoundByRole" => $groupAsRole !== null,
                "roleFoundByGroup" => $roleAsGroup !== null,
            ]));
        }


        /**
         *
         * @var Group Helper Functions
         *
         */
        private function echoGroup(?Group $group, bool $includePermissions, bool $includeUsers): void {
            echo(json_encode($this->getGroup($group, $includePermissions, $includeUsers)));
        }

        private function echoGroups(array $groups, bool $includePermissions, bool $includeUsers): void {
            echo(json_encode($this->getGroups($groups, $includePermissions, $includeUsers)));
        }

        private function getGroups(array $groups, bool $includePermissions, bool $includeUsers): array {
            $result = [];
            foreach ($groups as $group) {
                $result[] = $this->getGroup($group, $includePermissions, $includeUsers);
            }
            return $result;
        }

        private function getGroup(?Group $group, bool $includePermissions, bool $includeUsers): array {
            if($group === null) {
                return [
                    "found" => false
                ];
            }

            $groupData = [
                'id' => $group->id(),
                'name' => $group->name(),
            ];

            if($includePermissions) {
                $groupData['permissions'] = $group->getPermissions();
            }

            if($includeUsers) {
                $users = $group->getUsers();

                foreach($users as $user) {
                    $groupData['users'][] = [
                        'id' => $user->id(),
                        'name' => $user->email()
                    ];
                }
            }

            return $groupData;
        }

    }

?>
