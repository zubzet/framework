<?php

    use ZubZet\Framework\Translation\Translation;
    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Authentication\PasswordHash\Password;

    class ZOrganizationController {

        public function rename(Request $req, Response $res) {
            $req->checkPermission("z.organization.rename");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
            if(is_null($organization)) return $res->error("invalid_organization");

            $name = $req->getPost("name");
            if(is_string($name)) $name = trim($name);

            $formResult = $req->validateForm([
                (new FormField("name"))->required()->length(3, 255),
            ], ["name" => $name]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $organization->updateName($name);
            return $res->success();
        }

        public function roles(Request $req, Response $res) {
            $req->checkPermission("z.organization.roles");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            // Only members of the own organization
            $member = User::byId((int) $req->getRouteParameter("id"));
            if(is_null($organization) || is_null($member) || $member->organization()?->id() !== $organization->id()) {
                return $res->error("invalid_member");
            }

            // Only roles released to organizations
            $assignableRoleIds = array_column(model("z_organization")->getAssignableRoles(), "id");
            $formResult = $req->validateForm([
                (new FormField("roles"))->in($assignableRoleIds),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            // The selection replaces the assignable roles the member holds, an empty one is not posted at all
            $selectedRoleIds = (array) $req->getPost("roles", []);
            foreach($assignableRoleIds as $roleId) {
                model("z_user")->changeRoleStateByUserIdAndRoleId($member->id(), $roleId, in_array($roleId, $selectedRoleIds));
            }

            return $res->success();
        }

        public function delete(Request $req, Response $res) {
            $req->checkPermission("z.organization.delete");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            // Only active members of the own organization
            $member = User::byId((int) $req->getRouteParameter("id"));
            if(is_null($organization) || is_null($member) || $member->organization()?->id() !== $organization->id()) {
                return $res->error("invalid_member");
            }

            $member->remove();
            return $res->success();
        }

        public function remove(Request $req, Response $res) {
            $req->checkPermission("z.organization.remove");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            if(is_null($organization)) return $res->error("invalid_member");

            // Only members of the own organization, a deleted one is removed by the model
            $memberId = (int) $req->getRouteParameter("id");
            $member = User::byId($memberId);
            $isActiveMember = $member?->organization()?->id() === $organization->id();
            if(!$isActiveMember && !model("z_organization")->removeDeletedMember($organization, $memberId)) {
                return $res->error("invalid_member");
            }

            // The roles the organization handed out leave with the member
            foreach(model("z_organization")->getAssignableRoles() as $role) {
                model("z_user")->changeRoleStateByUserIdAndRoleId($memberId, $role["id"], false);
            }

            if($isActiveMember) {
                $member->updateOrganization(null);
            } else if(!is_null($organization->getGroup())) {
                // The organization's group, which updateOrganization() would take away
                model("z_user")->changeRoleStateByUserIdAndRoleId($memberId, $organization->getGroup()->id(), false);
            }

            return $res->success();
        }

        public function invite(Request $req, Response $res) {
            $req->checkPermission("z.organization.invite");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
            if(is_null($organization)) return $res->error("invalid_organization");

            // Only roles released to organizations, an empty selection invites without a role
            $assignableRoleIds = array_column(model("z_organization")->getAssignableRoles(), "id");
            $formResult = $req->validateForm([
                (new FormField("email"))->required()->filter(FILTER_VALIDATE_EMAIL),
                (new FormField("roleId"))->filter(FILTER_VALIDATE_INT)->in($assignableRoleIds),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $email = $req->getPost("email");
            $roleId = $req->getPost("roleId");

            // An address with an account, deactivated included, gets a message that does not reveal the account
            if(!db()->checkIfUnique("z_user", "email", $email)) {
                $formResult->addCustomError("email", "user_unavailable");
            } else if(model("z_organization")->hasOpenInvite($organization, $email)) {
                // One open invite per address and organization
                $formResult->addCustomError("email", "already_invited");
            } else if(model("z_organization")->isFull($organization)) {
                // Members and open invitations already fill the capacity
                $formResult->addCustomError("email", "organization_full");
            }

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $token = model("z_organization")->createInvite($organization, $email, empty($roleId) ? null : (int) $roleId);
            $link = config("root") . "z/organization/invitation/" . $token;

            // Mailing needs its own permission, without it the inviter always gets the link
            if(!filter_var($req->getPost("send_email"), FILTER_VALIDATE_BOOLEAN) || !$req->checkPermission("z.organization.invite.email", true)) {
                return $res->success(["invite_link" => $link]);
            }

            // The mail alone proves the address, so the inviter does not get the link
            $locale = Translation::locale();
            $sent = $res->sendEmail(
                $email,
                __("email.organization_invite.subject", ["{organization}" => $organization->getField("name")], locale: $locale),
                "email_organization_invite.php",
                $locale,
                [
                    "invite_link" => $link,
                    "organization" => $organization->getField("name"),
                    "locale" => $locale,
                ],
                "mail",
            );

            if(!$sent) {
                model("z_organization")->deactivateInvite($organization, model("z_organization")->getInviteByToken($token)["id"]);
                return $res->error("mail_failed");
            }

            return $res->success();
        }

        public function revoke(Request $req, Response $res) {
            $req->checkPermission("z.organization.invite");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
            if(is_null($organization)) return $res->error("invalid_organization");

            // Only invites of the own organization may be revoked
            $invite = model("z_organization")->getInvite($organization, (int) $req->getRouteParameter("id"));
            if(is_null($invite)) return $res->error("invalid_invite");

            model("z_organization")->deactivateInvite($organization, $invite["id"]);
            return $res->success();
        }

        public function accept(Request $req, Response $res) {
            $token = $req->getRouteParameter("token");

            $invite = empty($token) ? null : model("z_organization")->getInviteByToken($token);
            $organization = is_null($invite) ? null : Organization::byId($invite["organizationId"]);

            // Unknown, expired, its organization is gone or the address has an account by now
            if(is_null($organization) || !db()->checkIfUnique("z_user", "email", $invite["email"])) {
                return $res->error("invalid_token");
            }

            $formResult = $req->validateForm([
                (new FormField("password"))->required()->length(Password::MIN_LENGTH_BYTES, Password::MAX_LENGTH_BYTES),
                (new FormField("password_repeat"))->required(),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $password = $req->getPost("password");

            // An array passes the length rule by its item count
            if(!is_string($password)) return $res->error("Invalid input");

            if($password !== $req->getPost("password_repeat")) {
                $formResult->addCustomError("password_repeat", "password_mismatch");
                return $res->formErrors($formResult->errors);
            }

            if(model("z_organization")->isFull($organization, $invite["id"])) {
                $formResult->addCustomError("password", "organization_full");
                return $res->formErrors($formResult->errors);
            }

            // The invitation vouches for the address, so the account starts verified
            $user = User::add($invite["email"], $password, new DateTime());
            $user->updateOrganization($organization);

            // Only while the role is still released to organizations
            $assignableRoleIds = array_column(model("z_organization")->getAssignableRoles(), "id");
            if(!is_null($invite["roleId"]) && in_array($invite["roleId"], $assignableRoleIds)) {
                model("z_user")->changeRoleStateByUserIdAndRoleId($user->id(), $invite["roleId"]);
            }

            model("z_organization")->deactivateInvite($organization, $invite["id"]);

            return $res->success();
        }

    }

?>
