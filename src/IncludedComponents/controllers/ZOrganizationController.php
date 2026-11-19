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
            $isOwnMember = !is_null($organization) && $member?->organization()?->id() === $organization->id();
            if(!$isOwnMember) return $res->error("invalid_member");

            $member->remove();
            return $res->success();
        }

        public function remove(Request $req, Response $res) {
            $req->checkPermission("z.organization.remove");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            if(is_null($organization)) return $res->error("invalid_member");

            // Only members of the own organization
            $memberId = (int) $req->getRouteParameter("id");
            $member = User::byId($memberId);
            $isActiveMember = $member?->organization()?->id() === $organization->id();

            if(!$isActiveMember) {
                // A deleted member is invisible to User::byId(), the model detaches it
                $wasDetached = model("z_organization")->removeDeletedMember($organization, $memberId);
                if(!$wasDetached) return $res->error("invalid_member");

                // The organization's group, which updateOrganization() would take away
                $group = $organization->getGroup();
                if(!is_null($group)) {
                    model("z_user")->changeRoleStateByUserIdAndRoleId($memberId, $group->id(), false);
                }
            }

            // The roles the organization handed out leave with the member
            foreach(model("z_organization")->getAssignableRoles() as $role) {
                model("z_user")->changeRoleStateByUserIdAndRoleId($memberId, $role["id"], false);
            }

            if($isActiveMember) {
                $member->updateOrganization(null);
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

            $error = match(true) {
                // An address with an account, deactivated included, gets a message that does not reveal the account
                !db()->checkIfUnique("z_user", "email", $email) => "user_unavailable",
                // One open invite per address and organization
                model("z_organization")->hasOpenInvite($organization, $email) => "already_invited",
                // Members and open invitations already fill the capacity
                model("z_organization")->isFull($organization) => "organization_full",
                default => null,
            };

            if(!is_null($error)) {
                $formResult->addCustomError("email", $error);
                return $res->formErrors($formResult->errors);
            }

            $invite = model("z_organization")->createInvite($organization, $email, $formResult->getValue("roleId"));
            $link = config("root") . "z/organization/invitation/" . $invite["token"];

            // Mailing needs its own permission, without it the inviter always gets the link
            $sendEmail = filter_var($req->getPost("send_email"), FILTER_VALIDATE_BOOLEAN)
                && $req->checkPermission("z.organization.invite.email", true);
            if(!$sendEmail) return $res->success(["invite_link" => $link]);

            // The mail alone proves the address, so the inviter does not get the link
            $organizationName = $organization->getField("name");
            $locale = Translation::locale();
            $sent = $res->sendEmail(
                $email,
                __("email.organization_invite.subject", ["{organization}" => $organizationName], locale: $locale),
                "email_organization_invite.php",
                $locale,
                [
                    "invite_link" => $link,
                    "organization" => $organizationName,
                    "locale" => $locale,
                ],
                "mail",
            );

            if(!$sent) {
                model("z_organization")->deactivateInvite($organization, $invite["id"]);
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

            $formResult = $req->validateForm([
                (new FormField("password"))->required()->length(Password::MIN_LENGTH_BYTES, Password::MAX_LENGTH_BYTES),
                (new FormField("password_repeat"))->required(),
            ]);

            // Unknown, expired, its organization is gone or the address has an account by now
            if(is_null($organization) || !db()->checkIfUnique("z_user", "email", $invite["email"])) {
                $formResult->addCustomError("password", "invalid_token");
            }

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

            // Like a signup, the account stays unverified until its owner confirms the address
            $user = User::add($invite["email"], $password);
            $user->updateOrganization($organization);

            // Only while the role is still released to organizations
            $assignableRoleIds = array_column(model("z_organization")->getAssignableRoles(), "id");
            if(!is_null($invite["roleId"]) && in_array($invite["roleId"], $assignableRoleIds)) {
                model("z_user")->changeRoleStateByUserIdAndRoleId($user->id(), $invite["roleId"]);
            }

            model("z_organization")->deactivateInvite($organization, $invite["id"]);

            $token = model("z_user")->createVerifyToken($user->id());
            $res->sendEmailToUser($user->id(), config("pageName", default: "") . " - Sign Up", "email_verify.php", [
                "url" => config("root") . "login/verify/" . $token,
            ]);

            return $res->success();
        }

    }

?>
