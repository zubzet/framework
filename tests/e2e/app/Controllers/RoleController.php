<?php

    use ZubZet\Framework\Permission\Role;
    use ZubZet\Framework\Permission\User;

    class RoleController extends z_controller {

        /**
         *
         * @var Role Getters
         *
         */
        public function action_byId(Request $req, Response $res): void {
            $role = Role::byId(200);
            $this->echoRole($role, false, false);
        }

        public function action_byIdInactive(Request $req, Response $res): void {
            $role = Role::byId(201);
            $this->echoRole($role, false, false);
        }

        public function action_byIds(Request $req, Response $res): void {
            $roles = Role::byIds(202, 203, 204);
            $this->echoRoles($roles, false, false);
        }

        public function action_byName(Request $req, Response $res): void {
            $role = Role::byName("role_byName_Active");
            $this->echoRole($role, false, false);
        }

        public function action_byNameInactive(Request $req, Response $res): void {
            $role = Role::byName("role_byName_Inactive");
            $this->echoRole($role, false, false);
        }

        public function action_byUser(Request $req, Response $res): void {
            $roles = Role::byUser(User::byId(200));
            $this->echoRoles($roles, false, false);
        }

        public function action_byAccessToAll(Request $req, Response $res): void {
            $roles = Role::byAccessToAll("role_byAccessToAll.1", "role_byAccessToAll.2");
            $this->echoRoles($roles, false, false);
        }

        public function action_byAccessToAnyOf(Request $req, Response $res): void {
            $roles = Role::byAccessToAnyOf("role_byAccessToAnyOf.1", "role_byAccessToAnyOf.2");
            $this->echoRoles($roles, false, false);
        }


        /**
         *
         * @var Role Interactions
         *
         */
        public function action_remove(Request $req, Response $res): void {
            $role = Role::byId(209);
            $role->remove();

            $roleAfterDeletion = Role::byId(209);
            $this->echoRole($roleAfterDeletion, false, false);
        }

        public function action_removeInteraction(Request $req, Response $res): void {
            $role = Role::byId(210);
            $role->remove();

            try {
                $role->name();
            } catch(Exception $e) {
                $this->echoRole(null, false, false);
            }
        }

        public function action_update(Request $req, Response $res): void {
            $role = Role::byId(211);
            $role->update("role_update_NewName");

            $this->echoRole(Role::byId(211), false, false);
        }

        public function action_refresh(Request $req, Response $res): void {
            $role = Role::byId(212);

            $role->update("role_refresh_UpdatedName");
            $role->refresh();
            $this->echoRole($role, false, false);
        }

        public function action_getPermissions(Request $req, Response $res): void {
            $role = Role::byId(213);

            $this->echoRole($role, true, false);
        }

        public function action_getUsers(Request $req, Response $res): void {
            $role = Role::byId(214);

            $this->echoRole($role, false, true);
        }

        public function action_add(Request $req, Response $res): void {
            $role = Role::add("role_add_NewRole");
            $createdRoleDirect = $this->getRole($role, false, false);

            $role = Role::byId($role->id());
            $createdRoleGet = $this->getRole($role, false, false);


            echo(json_encode([
                "createdRoleDirect" => $createdRoleDirect,
                "createdRoleGet" => $createdRoleGet
            ]));
        }

        public function action_hasAccessToAll(Request $req, Response $res): void {
            echo(json_encode([
                "hasAccessToAll_15" => Role::byId(215)->hasAccessAll("role_hasAccessToAll.1", "role_hasAccessToAll.2"),
                "hasAccessToAll_16" => Role::byId(216)->hasAccessAll("role_hasAccessToAll.1", "role_hasAccessToAll.2"),
            ]));
        }

        public function action_hasAccessToAnyOf(Request $req, Response $res): void {
            echo(json_encode([
                "hasAccessToAnyOf_17" => Role::byId(217)->hasAccessAnyOf("role_hasAccessToAnyOf.1", "role_hasAccessToAnyOf.2"),
                "hasAccessToAnyOf_18" => Role::byId(218)->hasAccessAnyOf("role_hasAccessToAnyOf.1", "role_hasAccessToAnyOf.2"),
            ]));
        }



        /**
         *
         * @var Role Helper Functions
         *
         */
        private function echoRole(?Role $role, bool $includePermissions, bool $includeUsers): void {
            echo(json_encode($this->getRole($role, $includePermissions, $includeUsers)));
        }

        private function echoRoles(array $roles, bool $includePermissions, bool $includeUsers): void {
            echo(json_encode($this->getRoles($roles, $includePermissions, $includeUsers)));
        }

        private function getRoles(array $roles, bool $includePermissions, bool $includeUsers): array {
            $result = [];
            foreach ($roles as $role) {
                $result[] = $this->getRole($role, $includePermissions, $includeUsers);
            }
            return $result;
        }

        private function getRole(?Role $role, bool $includePermissions, bool $includeUsers): array {
            if($role === null) {
                return [
                    "found" => false
                ];
            }

            $userData = [
                'id' => $role->id(),
                'name' => $role->name(),
            ];

            if($includePermissions) {
                $userData['permissions'] = $role->getPermissions();
            }

            if($includeUsers) {
                $users = $role->getUsers();

                foreach($users as $user) {
                    $userData['users'][] = [
                        'id' => $user->id(),
                        'name' => $user->email()
                    ];
                }
            }

            return $userData;
        }

    }

?>