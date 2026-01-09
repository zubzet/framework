<?php

use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query\InsertQuery;
use ZubZet\Framework\Permission\Role;
use ZubZet\Framework\Permission\User;

    class z_permissionModel extends z_model {

        /**
         * Retrieve a permission object (user or role) by id and table
         *
         * @param int|string $id The ID of the object to retrieve
         * @param string $table The database table to query
         * @return array|null The data array of the object if found, null otherwise
         * @internal
         */
        public function getById(int|string $id, string $table): ?array {
            $query = $this->dbSelect("*", $table)->where([
                "id" => $id
            ])->where([
                "active" => 1
            ]);

            return $this->exec($query)->resultToLine();
        }

        /**
         * Retrieve permission objects (user or role) by their ids
         *
         * @param int ...$ids The ids of the objects to retrieve
         * @param string $table The database table to query
         * @return array[] An array of permission objects
         * @internal
         */
        public function getByIds(string $table, int ...$ids): array {
            $query = $this->dbSelect("*", $table)->whereInList("id", $ids)->where([
                "active" => 1
            ]);

            return $this->exec($query)->resultToArray();
        }

        /**
         * Retrieve all permission objects (users or roles)
         *
         * @param string $table The database table to query
         * @return array An array of permission objects
         * @internal
         */
        public function getAll(string $table): array {
            $objects = [];

            $query = $this->dbSelect("*", $table);
            $query->where([
                "active" => 1
            ]);

            $results = $this->exec($query)->resultToArray();

            foreach($results as $result) {
                $objects[] = $result;
            }

            return $objects;
        }


        /**
         * Add permissions to a permission object (user or role)
         *
         * @param int|string $objectId The id of the object to add permissions to
         * @param string $permissionsTable The permissions table
         * @param string $column The column name for the object ID
         * @param string[] $permissionNames The names of the permissions to add
         * @return void
         * @internal
         */
        public function addPermissionsToObject(int|string $objectId, string $permissionsTable, string $column, string ...$permissionNames): void {
            $insertQuery = new InsertQuery($this->getQueryBuilder());
            $insertQuery->into($permissionsTable);
            $insertQuery->insert([$column, 'name']);

            foreach($permissionNames as $permName) {
               $insertQuery->values([
                    $column => $objectId,
                    "name" => $permName
               ]);
            }

            $this->exec($insertQuery);
        }

        /**
         * Remove permissions from a permission object (user or role)
         *
         * @param int|string $objectId The id of the object to remove permissions from
         * @param string $permissionsTable The permissions table
         * @param string $column The column name for the object ID
         * @param string[] $permissionNames The names of the permissions to remove
         * @return void
         * @internal
         */
        public function removePermissionsFromObject(int|string $objectId, string $permissionsTable, string $column, string ...$permissionNames): void {
            $deleteQuery = $this->dbUpdate($permissionsTable, [
                "active" => 0
            ])->where([
                $column => $objectId
            ])->whereInList("name", $permissionNames);

            $this->exec($deleteQuery);
        }


        /**
         * @internal
         */
        public function getRolesByUsers(User $user): array {
            $roles = [];

            $query = $this->dbSelect("zr.*", [
                "zur" => "z_user_role"
            ])->where([
                "user" => $user->id(),
                "zur.active" => 1,
                "zr.active" => 1,
            ])->leftJoin([
                "zr" => "z_role"
            ], "zur.role = zr.id");

            $results = $this->exec($query)->resultToArray();

            foreach($results as $role) {
                $roles[] = new Role($role);
            }

            return $roles;
        }

        /**
         * @internal
         */
        public function getPermissionsByRole(Role $role): array {
            $perms = [];

            $query = $this->dbSelect("zrp.*", [
                "zrp" => "z_role_permission"
            ])->where([
                "zrp.role" => $role->id(),
                "zrp.active" => 1
            ]);

            $results = $this->exec($query)->resultToArray();

            foreach($results as $row) {
                $perms[] = $row['name'];
            }

            return $perms;
        }

        /**
         * @internal
         */
        public function getUsersByRole(Role $role): array {
            $users = [];

            $query = $this->dbSelect("zu.*", [
                "zur" => "z_user_role"
            ])->where([
                "role" => $role->id(),
                "zur.active" => 1,
                "zu.active" => 1
            ])->leftJoin([
                "zu" => "z_user"
            ], "zur.user = zu.id");

            $results = $this->exec($query)->resultToArray();

            foreach($results as $userData) {
                $users[] = new User($userData);
            }

            return $users;
        }

        /**
         * @internal
         */
        public function removeUser(User $user) {
            $query = $this->dbUpdate("z_user", [
                "active" => 0
            ])->where([
                "id" => $user->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

        /**
         * @internal
         */
        public function getPermissionsByUserAll(User $user): array {
            $userPermissions = $this->dbSelect("zup.name", [
                "zup" => "z_user_permission"
            ])->where([
                "zup.user" => $user->id(),
                "zup.active" => 1
            ]);

            $userRolePermissions = $this->dbSelect("zrp.name", [
                "zur" => "z_user_role"
            ])->where([
                "zur.user" => $user->id(),
                "zur.active" => 1,
                "zrp.active" => 1,
                "zr.active" => 1
            ])->leftJoin([
                "zrp" => "z_role_permission"
            ], "zur.role = zrp.role")
            ->leftJoin([
                "zr" => "z_role"
            ], "zur.role = zr.id");

            $unionQuery = $userPermissions->union($userRolePermissions);

            $finalPermissions = $this->dbSelect("perm.name", [
                "perm" => $unionQuery
            ])->distinct(true);

            return $this->exec($finalPermissions)->resultToArray();
        }

        /**
         * @internal
         */
        public function getPermissionsByUser(User $user): array {
            $userPermissions = $this->dbSelect("zup.name", [
                "zup" => "z_user_permission"
            ])->where([
                "zup.user" => $user->id(),
                "zup.active" => 1
            ])->distinct(true);

            return $this->exec($userPermissions)->resultToArray();
        }

        /**
         * @internal
         */
        public function getUserByEmail(string $email): ?User {
            $query = $this->dbSelect("zu.*", [
                "zu" => "z_user"
            ])->where([
                "zu.email" => $email,
                "zu.active" => 1
            ])->limit(1)->whereNotNull("zu.email");

            $result = $this->exec($query)->resultToLine();

            if(is_null($result)) return null;

            return new User($result);
        }

        /**
         * Get a list of users who were not verified as of the $since datetime
         *
         * @param DateTime $since When to check for non-verified users
         * @return User[] array of User objects
         * @internal
         */
        public function getNotVerifiedUsers(DateTime $since): array {
            $users = [];

            $query = $this->dbSelect('zu.*', [
                'zu' => 'z_user'
            ])->where([
                'zu.active' => 1,
                'OR' => [
                    'zu.verified IS NULL',
                    function ($exp) use ($since) {
                        return $exp->gt('zu.verified', $since->format('Y-m-d H:i:s'));
                    }
                ]
            ]);

            $results = $this->exec($query)->resultToArray();

            foreach($results as $userData) {
                $users[] = new User($userData);
            }

            return $users;
        }

        /**
         * @internal
         */
        public function getRoleByName(string $name): ?Role {
            $query = $this->dbSelect("zr.*", [
                "zr" => "z_role"
            ])->where([
                "zr.name" => $name,
                "zr.active" => 1
            ]);

            $result = $this->exec($query)->resultToLine();

            if(is_null($result)) return null;

            return new Role($result);
        }

        /**
         * @internal
         */
        public function addRole(string $rolename): array {
            $query = $this->dbInsert("z_role", [
                "name" => $rolename,
            ]);

            $insertedId = $this->exec($query)->getInsertId();

            return $this->getById($insertedId, "z_role");
        }

        /**
         * @internal
         */
        public function updateRole(Role $role, string $newName): void {
            $query = $this->dbUpdate("z_role", [
                "name" => $newName
            ])->where([
                "id" => $role->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

        /**
         * @internal
         */
        public function removeRole(Role $role): void {
            $query = $this->dbUpdate("z_role", [
                "active" => 0
            ])->where([
                "id" => $role->id(),
                "active" => 1
            ]);

            $this->exec($query);
        }

        /**
         * @internal
         */
        public function updateUserEmail(User $user, ?string $newEmail): void {
            $updateQuery = $this->dbUpdate("z_user", [
                "email" => $newEmail
            ])->where([
                "id" => $user->id(),
                "active" => 1
            ]);

            $this->exec($updateQuery);
        }

        /**
         * @internal
         */
        public function addRolesToUser(User $user, Role ...$roles): void {
            $insertQuery = new InsertQuery($this->getQueryBuilder());
            $insertQuery->into("z_user_role");
            $insertQuery->insert(["user", "role"]);

            foreach($roles as $role) {
               $insertQuery->values([
                    "user" => $user->id(),
                    "role" => $role->id()
               ]);
            }

            $this->exec($insertQuery);
        }

        /**
         * @internal
         */
        public function removeRolesFromUser(User $user, Role ...$roles): void {
            $roleIds = array_map(fn($role) => $role->id(), $roles);

            $deleteQuery = $this->dbUpdate("z_user_role", [
                "active" => 0
            ])->where([
                "user" => $user->id()
            ])->whereInList("role", $roleIds);

            $this->exec($deleteQuery);
        }

        /**
         * Verify a user
         *
         * @param User $user The user to verify
         * @param DateTime $dateTime The date and time of verification
         * @return void
         * @internal
         */
        public function verifyUser(User $user, DateTime $dateTime): void {
            $updateQuery = $this->dbUpdate("z_user", [
                "verified" => $dateTime->format("Y-m-d H:i:s")
            ])->where([
                "id" => $user->id()
            ]);

            $this->exec($updateQuery);
        }

        public function getRolesByAccessToAnyOf(array $extractedPermissions): array {
            $query = $this->dbSelect("t.*", ["t" => "z_role"])->distinct(true);

            $query->leftJoin(
                ["p" => "z_role_permission"],
                ["p.role = t.id"]
            );

            $query->where(function($exp) use ($extractedPermissions) {
                return $exp->in("p.name", $extractedPermissions)->add("p.name IS NOT NULL");
            });

            $query->where([
                "p.active" => 1,
                "t.active" => 1
            ]);

            $query->group(["t.id"]);

            $roleData = $this->exec($query)->resultToArray();
            $roleObjects = [];
            foreach($roleData as $roleRow) {
                $roleObjects[] = new Role($roleRow);
            }

            return $roleObjects;
        }

        public function getRolesByAccessToAll(array $extractedPermissionsGroup): array {
            $query = $this->dbSelect("t.*", ["t" => "z_role"]);

            foreach($extractedPermissionsGroup as $i => $variants) {
                $alias = "p{$i}";

                $query->leftJoin(
                    [$alias => "z_role_permission"],
                    ["$alias.role = t.id"]
                );

                $query->where([
                    "$alias.active" => 1
                ]);

                $query->where(function($exp) use ($alias, $variants) {
                    return $exp->in("$alias.name", $variants)->add("$alias.name IS NOT NULL");
                });
            }

            $query->group(["t.id"]);
            $query->where([
                "t.active" => 1
            ]);

            $roles = $this->exec($query)->resultToArray();
            $roleObjects = [];

            foreach($roles as $roleData) {
                $roleObjects[] = new Role($roleData);
            }

            return $roleObjects;
        }

        public function getUsersByAccessToAll(array $extractedPermissionsGroup): array {
            $qb = $this->getQueryBuilder();

            $query = $qb->selectQuery()
                ->select(['zu.*'])
                ->from(['zu' => 'z_user'])
                ->where(['zu.active' => 1]);

            foreach ($extractedPermissionsGroup as $permissionsSet) {

                $srcUser = $this->getQueryBuilder()
                    ->selectQuery()
                    ->select(['user' => 'up.user'])
                    ->from(['up' => 'z_user_permission'])
                    ->where(function (QueryExpression $exp) use ($permissionsSet) {
                        return $exp->in('up.name', $permissionsSet);
                    })->where([
                        'up.active' => 1
                    ]);

                $srcRole = $this->getQueryBuilder()
                    ->selectQuery()
                    ->select(['user' => 'ur.user'])
                    ->from(['ur' => 'z_user_role'])
                    ->join([
                        'rp' => [
                            'table' => 'z_role_permission',
                            'type' => 'INNER',
                            'conditions' => 'rp.role = ur.role',
                        ],
                    ])
                    ->where(function (QueryExpression $exp) use ($permissionsSet) {
                        return $exp->in('rp.name', $permissionsSet);
                    })
                    ->where([
                        'ur.active' => 1,
                        'rp.active' => 1
                    ])
                    ->leftJoin([
                        'r' => 'z_role'
                    ], 'ur.role = r.id')
                    ->where([
                        'r.active' => 1
                    ]);

                $union = $srcUser->unionAll($srcRole);

                $exists = $this->getQueryBuilder()
                    ->selectQuery()
                    ->select(['1'])
                    ->from(['x' => $union])
                    ->where(function (QueryExpression $exp) {
                        return $exp->eq('x.user', new IdentifierExpression('zu.id'));
                    });

                $query->where(fn (QueryExpression $exp) => $exp->exists($exists));
            }

            $users = $this->exec($query)->resultToArray();
            $userObjects = [];
            foreach($users as $userData) {
                $userObjects[] = new User($userData);
            }

            return $userObjects;
        }

        public function getUsersByAccessToAnyOf(array $extractedPermissions): array {
            $qb = $this->getQueryBuilder();

            $query = $qb->selectQuery()
                ->select(['zu.*'])
                ->from(['zu' => 'z_user'])
                ->where(['zu.active' => 1]);

            $srcUser = $this->getQueryBuilder()
                ->selectQuery()
                ->select(['user' => 'up.user'])
                ->from(['up' => 'z_user_permission'])
                ->where(function (QueryExpression $exp) use ($extractedPermissions) {
                    return $exp->in('up.name', $extractedPermissions);
                })->where([
                    'up.active' => 1
                ]);

            $srcRole = $this->getQueryBuilder()
                ->selectQuery()
                ->select(['user' => 'ur.user'])
                ->from(['ur' => 'z_user_role'])
                ->join([
                    'rp' => [
                        'table' => 'z_role_permission',
                        'type' => 'INNER',
                        'conditions' => 'rp.role = ur.role',
                    ],
                ])
                ->where([
                    "rp.active" => 1,
                    "ur.active" => 1,
                ])
                ->where(function (QueryExpression $exp) use ($extractedPermissions) {
                    return $exp->in('rp.name', $extractedPermissions);
                })->leftJoin([
                    'r' => 'z_role'
                ], 'ur.role = r.id')->where([
                    'r.active' => 1
                ]);

            $union = $srcUser->unionAll($srcRole);

            $exists = $this->getQueryBuilder()
                ->selectQuery()
                ->select(['1'])
                ->from(['x' => $union])
                ->where(function (QueryExpression $exp) {
                    return $exp->eq('x.user', new IdentifierExpression('zu.id'));
                });

            $query->where(fn(QueryExpression $exp) => $exp->exists($exists));

            $result = $this->exec($query)->resultToArray();
            $userObjects = [];

            foreach($result as $userData) {
                $userObjects[] = new User($userData);
            }

            return $userObjects;
        }

    }

?>