<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\User;

    class ZOrganizationController {

        public function rename(Request $req, Response $res) {
            $req->checkPermission("z.organization.rename");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);
            if(is_null($organization)) return $res->error("invalid_organization");

            $formResult = $req->validateForm([
                (new FormField("name"))->required()->length(3, 255),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $organization->updateName($req->getPost("name"));
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

        public function invite(Request $req, Response $res) {
            $req->checkPermission("z.organization.invite");

            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            $formResult = $req->validateForm([
                (new FormField("email"))->required()->filter(FILTER_VALIDATE_EMAIL),
            ]);

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $email = $req->getPost("email");

            // Only an active account outside any organization can be invited, accepting would fail otherwise
            $invitee = User::byEmail($email);
            if(is_null($organization) || is_null($invitee) || !is_null($invitee->organization())) {
                $formResult->addCustomError("email", "user_unavailable");
            } else if(model("z_organization")->hasOpenInvite($organization, $email)) {
                // One open invite per address
                $formResult->addCustomError("email", "already_invited");
            }

            if($formResult->hasErrors) return $res->formErrors($formResult->errors);

            $token = model("z_organization")->createInvite($organization, $email);

            return $res->success([
                "invite_link" => config("root") . "z/organization/invitation/" . $token,
            ]);
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
            if(!user()->isLoggedIn) return $res->reroute(["login", "index"]);

            $token = $req->getRouteParameter("token");
            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            $invite = empty($token) ? null : model("z_organization")->getInviteByToken($token);
            $invitedOrganization = is_null($invite) ? null : Organization::byId($invite["organizationId"]);
            $user = User::byId(user()->userId);

            // Unknown, expired, meant for another address or its organization is gone
            $error = null;
            if(is_null($invitedOrganization) || 0 !== strcasecmp($invite["email"], $user->email())) {
                $error = "invalid_token";
            } else if(!is_null($organization)) {
                $error = "already_in_organization";
            }

            // The accept button shows the message
            if(!is_null($error)) return $res->error($error);

            $user->updateOrganization($invitedOrganization);
            model("z_organization")->deactivateInvite($invitedOrganization, $invite["id"]);

            return $res->success();
        }

    }

?>
