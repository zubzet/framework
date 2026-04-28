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

        public function action_userByOrganizationEmpty(Request $req, Response $res): void {
            $users = User::byOrganization(Organization::byId(511));

            $result = [];
            foreach($users as $user) {
                $result[] = [
                    "id" => $user->id(),
                    "email" => $user->email()
                ];
            }

            echo(json_encode($result));
        }


        /**
         *
         * @var Organization Helper Functions
         *
         */
        private function echoOrganization(?Organization $organization, bool $includeUsers): void {
            echo(json_encode($this->getOrganization($organization, $includeUsers)));
        }

        private function echoOrganizations(array $organizations, bool $includeUsers): void {
            $result = [];
            foreach($organizations as $organization) {
                $result[] = $this->getOrganization($organization, $includeUsers);
            }
            echo(json_encode($result));
        }

        private function getOrganization(?Organization $organization, bool $includeUsers): array {
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

            return $organizationData;
        }

    }

?>
