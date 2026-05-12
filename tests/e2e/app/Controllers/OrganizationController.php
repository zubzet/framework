<?php

    use ZubZet\Framework\Authentication\Organization;
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
        // demonstrate that User::byOrganization returns an empty list — no
        // need to iterate it (and create coverage noise on a body that, by
        // definition, can never run).
        public function action_userByOrganizationEmpty(Request $req, Response $res): void {
            $users = User::byOrganization(Organization::byId(511));
            echo(json_encode($users));
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
