<?php

use ZubZet\Framework\Authentication\Permission\Role;
use ZubZet\Framework\Authentication\Permission\User;

class UserController extends z_controller {

    /**
     *
     * @var User Getters
     *
     */
    public function action_byId(Request $req, Response $res): void {
        $user = User::byId(100);
        $this->echoUser($user, false, false);
    }

    public function action_byIdInactive(Request $req, Response $res): void {
        $user = User::byId(101);
        $this->echoUser($user, false, false);
    }

    public function action_byEmail(Request $req, Response $res): void {
        $user = User::byEmail('user_byEmail_Active@cypress.test');
        $this->echoUser($user, false, false);
    }

    public function action_byEmailInactive(Request $req, Response $res): void {
        $user = User::byEmail('user_byEmail_Inactive@cypress.test');
        echo(json_encode($this->getUser($user, false, false)));
    }

    public function action_byNotVerified(Request $req, Response $res): void {
        $since = new DateTime('2028-01-01 00:00:00');

        $users = User::byNotVerified($since);
        $this->echoUsers($users, false, false);
    }

   public function action_byRole(Request $req, Response $res): void {
        $users = User::byRole(Role::byId(100));
        $this->echoUsers($users, false, true);
    }

    public function action_byIds(Request $req, Response $res): void {
        $users = User::byIds(127, 128, 129, 130, 131);
        $this->echoUsers($users, false, false);
    }

    public function action_byAccessToAll(Request $req, Response $res): void {
        $users = User::byAccessToAll("user.byAccessToAll.1", "user.byAccessToAll.2");
        $this->echoUsers($users, false, false);
    }

    public function action_byAccessToAnyOf(Request $req, Response $res): void {
        $users = User::byAccessToAnyOf("user.byAccessToAnyOf.1", "user.byAccessToAnyOf.2");
        $this->echoUsers($users, false, false);
    }

    /**
     *
     * @var User Interactions
     *
     */
    public function action_remove(Request $req, Response $res): void {
        $user = User::byId(116);
        $user->remove();

        $userAfterDeletion = User::byId(116);

        $this->echoUser($userAfterDeletion, false, false);
    }

    public function action_removeInteraction(Request $req, Response $res): void {
        $user = User::byId(117);
        $user->remove();

        try {
            echo($user->email());
        } catch(InvalidArgumentException $e) {
            $this->echoUser(null, false, false);
        }
    }

    public function action_updateEmail(Request $req, Response $res): void {
        $user = User::byId(118);
        $user->updateEmail('user_updateEmail-updated@update.cypress.test');

        $this->echoUser(User::byId(118), false, false);
    }

    public function action_updatePassword(Request $req, Response $res): void {
        $user = User::byId(119);
        $isOldPasswordCorrect = $req->getModel("z_login")->checkPassword("password", $user->getField("password"), $user->getField("salt"));

        $user->updatePassword("newpassword");

        $user = User::byId(119); // Reload user to get updated password hash
        $isOldPasswordCorrectAfterUpdate = $req->getModel("z_login")->checkPassword("password", $user->getField("password"), $user->getField("salt"));
        $isNewPasswordCorrect = $req->getModel("z_login")->checkPassword("newpassword", $user->getField("password"), $user->getField("salt"));

        echo(json_encode([
            "isOldPasswordCorrect" => $isOldPasswordCorrect,
            "isOldPasswordCorrectAfterUpdate" => $isOldPasswordCorrectAfterUpdate,
            "isNewPasswordCorrect" => $isNewPasswordCorrect
        ]));
    }

    public function action_verify(Request $req, Response $res): void {
        $user = User::byId(120);
        $beforeVerified = $this->getUser($user, false, false);

        $user->verify();
        $user = User::byId(120); // Reload user to get updated verified status
        $afterVerified = $this->getUser($user, false, false);

        echo(json_encode([
            "beforeVerified" => $beforeVerified,
            "afterVerified" => $afterVerified
        ]));
    }

    public function action_verifySpecific(Request $req, Response $res): void {
        $user = User::byId(121);
        $beforeVerified = $this->getUser($user, false, false);

        $specificDate = new DateTime('2023-01-01 12:00:00');
        $user->verify($specificDate);
        $user = User::byId(121); // Reload user to get updated verified status
        $afterVerified = $this->getUser($user, false, false);

        echo(json_encode([
            "beforeVerified" => $beforeVerified,
            "afterVerified" => $afterVerified
        ]));
    }

    public function action_isVerifiedOnNull(Request $req, Response $res): void {
        $user = User::byId(122);
        $isVerifiedNow = $user->isVerified("NOW");
        $isVerifiedPast = $user->isVerified("2020-01-01 00:00:00");

        echo(json_encode([
            "isVerifiedNow" => $isVerifiedNow,
            "isVerifiedPast" => $isVerifiedPast
        ]));
    }

    public function action_isVerifiedNotNull(Request $req, Response $res) {
        $user = User::byId(123);
        $isVerifiedNow = $user->isVerified("NOW");
        $isVerifiedPast = $user->isVerified("2020-01-01 00:00:00");
        $isVerifiedFuture = $user->isVerified("2030-01-01 00:00:00");

        echo(json_encode([
            "isVerifiedNow" => $isVerifiedNow,
            "isVerifiedPast" => $isVerifiedPast,
            "isVerifiedFuture" => $isVerifiedFuture
        ]));
    }

    public function action_refresh(Request $req, Response $res) {
        $user = User::byId(124);
        $user->updateEmail("user_refresh-updated@update.cypress.test");
        $user->refresh();

        $this->echoUser($user, false, false);
    }

    public function action_getRoles(Request $req, Response $res): void {
        $user = User::byId(125);

        $this->echoUser($user, false, true);
    }

    public function action_getPermissions(Request $req, Response $res): void {
        $user = User::byId(126);

        $this->echoUser($user, true, false);
    }

    public function action_add(Request $req, Response $res): void {
        $user = User::add("user_add@cypress.test", "password123", new DateTime("2005-01-01 00:00:00"));
        $createdUserDirect = $this->getUser($user, false, false);

        $user = User::byId($user->id());
        $createdUserGet = $this->getUser($user, false, false);

        $passwordWorking = $req->getModel("z_login")->checkPassword("password123", $user->getField("password"), $user->getField("salt"));

        echo(json_encode([
            "createdUserDirect" => $createdUserDirect,
            "createdUserGet" => $createdUserGet,
            "passwordWorking" => $passwordWorking
        ]));
    }

    public function action_hasAccessToAll(Request $req, Response $res): void {
        echo(json_encode([
            "hasAccess_50" => User::byId(150)->hasAccessAll("user.hasAccessToAll.1", "user.hasAccessToAll.2"),
            "hasAccess_51" => User::byId(151)->hasAccessAll("user.hasAccessToAll.1", "user.hasAccessToAll.2")
        ]));
    }

    public function action_hasAccessToAnyOf(Request $req, Response $res): void {
        echo(json_encode([
            "hasAccess_52" => User::byId(152)->hasAccessAnyOf("user.hasAccessToAnyOf.1", "user.hasAccessToAnyOf.2"),
            "hasAccess_53" => User::byId(153)->hasAccessAnyOf("user.hasAccessToAnyOf.1", "user.hasAccessToAnyOf.2")
        ]));
    }



    /**
     *
     * @var User Helper Functions
     *
     */
    private function echoUser(?User $user, bool $includePermissions, bool $includeRoles): void {
        echo(json_encode($this->getUser($user, $includePermissions, $includeRoles)));
    }

    private function echoUsers(array $users, bool $includePermissions, bool $includeRoles): void {
        echo(json_encode($this->getUsers($users, $includePermissions, $includeRoles)));
    }

    private function getUsers(array $users, bool $includePermissions, bool $includeRoles): array {
        $result = [];
        foreach ($users as $user) {
            $result[] = $this->getUser($user, $includePermissions, $includeRoles);
        }
        return $result;
    }

    private function getUser(?User $user, bool $includePermissions, bool $includeRoles): array {
        if($user === null) {
            return [
                "found" => false
            ];
        }

        $userData = [
            'id' => $user->id(),
            'email' => $user->email(),
            'isVerified' => $user->isVerified(),
            'verified' => $user->verified()
        ];

        if ($includePermissions) {
            $userData['permissions'] = $user->getPermissions();
        }

        if ($includeRoles) {
            $roles = $user->getRoles();

            foreach($roles as $role) {
                $userData['roles'][] = [
                    'id' => $role->id(),
                    'name' => $role->name()
                ];
            }
        }

        return $userData;
    }

}

?>