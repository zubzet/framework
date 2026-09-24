<?php

    use ZubZet\Framework\Authentication\Organization;
    use ZubZet\Framework\Authentication\Permission\User;
    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Maintenance\MaintenanceHandler;
    use ZubZet\Framework\Message\Response;

    /**
     * The ZController contains actions for the admin dashboard / panel
     */
    class ZController extends z_controller {

        // An organization invite stays valid for seven days
        private const ORGANIZATION_INVITE_LIFETIME = TIMESPAN_DAY_7;

        public function __construct(Request $req, Response $res) {
            $res->setDefaultLayout("layout/z_admin_layout.php");
        }

        // Dashboard: one card per admin section the requesting user can access.
        public function action_index(Request $req, Response $res) {
            $req->checkPermission("admin.panel");
            return $res->render("administration/dashboard.php");
        }

        public function action_maintenance(Request $req, Response $res) {
            $req->checkPermission("admin.maintenance");

            if($req->isAction("bypass-maintenance")) {
                $res->setCookie(
                    MaintenanceHandler::$COOKIE_KEY,
                    "true",
                    time() + TIMESPAN_DAY_1,
                    $req->getRootFolder(),
                );
                return $res->success();
            }

            return $res->render("administration/maintenance.php", [
                "isActive" => MaintenanceHandler::isActive(),
                "mode" => MaintenanceHandler::getMode(),
                "browserCanBypass" => MaintenanceHandler::checkBypassCookie(),
            ]);
        }

        // Action for adding a user
        public function action_add_user(Request $req, Response $res) {
            $req->checkPermission("admin.user.add");

            if(!$req->hasFormData()) {
                return $res->render("administration/add_user.php");
            }

            $formResult = $req->validateForm([
                (new FormField("email"))->unique("z_user", "email"),
            ]);

            $email = $req->getPost("email");
            if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $formResult->addCustomError("email", "filter");
            }

            if($formResult->hasErrors) {
                return $res->formErrors($formResult->errors);
            }

            try {
                $req->getModel("z_user", $res->getZRoot())->add(
                    empty($email) ? null : $email,
                    $req->getPost("password"),
                    date("Y-m-d H:i:s"),
                );
            } catch(\Exception $e) {
                $formResult->addCustomError("password", "filter");
                return $res->formErrors($formResult->errors);
            }

            return $res->success();
        }

        // Action for editing a user
        public function action_edit_user(Request $req, Response $res) {
            $req->checkPermission("admin.user.list");

            $userId = $req->getParameters(0, 1);
            if(empty($userId) && $userId !== '0') {
                return $res->render("administration/user_select.php", [
                    "users" => $req->getModel("z_user")->getUserList(),
                ]);
            }

            $req->checkPermission("admin.user.edit");
            $user = $req->getModel("z_user")->getUserById($userId);
            $email = $user["email"];

            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("email"))->unique("z_user", "email", "id", $userId),
                ]);

                $subformResult = $req->validateCED("roles", [
                    (new FormField("role"))->required()->exists("z_role", "id"),
                ]);

                $subPermissionForm = $req->validateCED("permissions", [
                    (new FormField("name"))->required()->length(3, 100),
                ]);

                $newEmail = $req->getPost("email");
                if(!empty($newEmail) && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $formResult->addCustomError("email", "filter");
                }

                if($formResult->hasErrors || $subformResult->hasErrors) {
                    return $res->formErrors($formResult->errors, $subformResult->errors);
                }

                $res->doCED("z_user_role", $subformResult, ["user" => $userId]);
                $res->doCED("z_user_permission", $subPermissionForm, ["user" => $userId]);
                $res->updateDatabase("z_user", "id", "i", $userId, $formResult);

                logger(Logger::ZUBZET)->info(LogEventType::ACCOUNT_UPDATED, [
                    "userId" => $userId,
                    "email" => $email,
                ]);

                return $res->success();
            }

            return $res->render("administration/edit_user.php", [
                "users" => $this->makeFood($req->getModel("z_user")->getUserList(), "id", "email"),
                "roles" => $this->makeFood($req->getModel("z_general")->getTableWhere("z_role", "*", "active = 1 AND (is_group = 0 OR id IN (SELECT role FROM z_user_role WHERE active = 1 AND user = ?))", "i", [$userId]), "id", "name"),
                "user_permissions" => $this->makeCEDFood($req->getModel("z_general")->getTableWhere("z_user_permission", "*", "active = 1 AND user = ?", "i", [$userId]), ["name"]),
                "user_roles" => $this->makeCEDFood($req->getModel("z_user")->getRoles($userId), ["role"]),
                "result" => "success",
                "email" => $user["email"],
                "userId" => $userId,
            ]);
        }

        // Action for logging in as someone else
        public function action_login_as(Request $req, Response $res) {
            $req->checkPermission("admin.su");

            $userId = $req->getParameters(0, 1);
            if(empty($userId) && $userId !== '0') return;

            // The admin may state a reason; the column takes 255 characters
            $reason = trim((string) $req->getGet("reason", ""));
            if(empty($reason)) $reason = "z-admin impersonation";

            $res->loginAs($userId, $req->getRequestingUser()->execUserId, reason: mb_substr($reason, 0, 255));
            return $res->rerouteUrl();
        }


        public function action_groups(Request $req, Response $res) {
            $req->checkPermission("admin.groups.list");

            return $res->render("administration/groups.php", [
                "groups" => model("z_general")->getGroups(),
            ]);
        }

        public function action_organization(Request $req, Response $res) {
            if(!user()->isLoggedIn) return $res->reroute(["login", "index"]);

            $action = $req->getParameters(0, 1);
            $target = $req->getParameters(1, 1);
            $organization = is_null(user()->orgId) ? null : Organization::byId(user()->orgId);

            if("invite" === $action && $req->hasFormData("z-organization-invite")) {
                $req->checkPermission("z.organization.invite");

                $formResult = $req->validateForm([
                    (new FormField("email"))->required()->filter(FILTER_VALIDATE_EMAIL),
                ]);

                if($formResult->hasErrors) return $res->formErrors($formResult->errors);

                $email = $req->getPost("email");

                // Only an active account outside any organization can be invited, accepting would fail otherwise
                $invitee = User::byEmail($email);
                if(is_null($organization) || is_null($invitee) || !is_null($invitee->organization())) {
                    $formResult->addCustomError("email", "user_unavailable");
                } else {
                    // One open invite per address, an expired one no longer counts
                    $openInvites = array_filter(
                        model("z_organization")->getInvitesByEmail($organization, $email),
                        fn($invite) => strtotime($invite["created"]) + self::ORGANIZATION_INVITE_LIFETIME >= time(),
                    );

                    if(!empty($openInvites)) $formResult->addCustomError("email", "already_invited");
                }

                if($formResult->hasErrors) return $res->formErrors($formResult->errors);

                $token = model("z_organization")->createInvite($organization, $email);

                return $res->success([
                    "invite_link" => config("root") . "z/organization/invitation/" . $token,
                ]);
            }

            if("revoke" === $action) {
                $req->checkPermission("z.organization.invite");

                // Only invites of the own organization may be revoked
                $invite = model("z_organization")->getInviteById((int) $target);
                if(is_null($invite) || $invite["organizationId"] !== $organization?->id()) return $res->error("invalid_invite");

                model("z_organization")->deactivateInvite($invite["id"]);
                return $res->success();
            }

            if("roles" === $action && $req->hasFormData("z-organization-member-" . $target)) {
                $req->checkPermission("z.organization.roles");

                // Only members of the own organization
                $member = User::byId((int) $target);
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

            if("rename" === $action && $req->hasFormData("z-organization-rename")) {
                $req->checkPermission("z.organization.rename");

                if(is_null($organization)) return $res->error("invalid_organization");

                $formResult = $req->validateForm([
                    (new FormField("name"))->required()->length(3, 255),
                ]);

                if($formResult->hasErrors) return $res->formErrors($formResult->errors);

                $organization->updateName($req->getPost("name"));
                return $res->success();
            }

            if("invitation" === $action) {
                $isAccept = "accept" === $req->getParameters(2, 1);

                $invite = empty($target) ? null : model("z_organization")->getInviteByToken($target);
                $invitedOrganization = is_null($invite) ? null : Organization::byId($invite["organizationId"]);
                $user = User::byId(user()->userId);

                // Unknown, expired, meant for another address or its organization is gone
                $error = null;
                if(is_null($invitedOrganization)
                    || strtotime($invite["created"]) + self::ORGANIZATION_INVITE_LIFETIME < time()
                    || 0 !== strcasecmp($invite["email"], $user->email())
                ) {
                    $error = "invalid_token";
                } else if(!is_null($organization)) {
                    $error = "already_in_organization";
                }

                if(!is_null($error)) {
                    // The accept button shows the message, the page answers like an unknown address
                    if($isAccept) return $res->error($error);
                    return zubzet()->executePath(["error", "404"]);
                }

                if($isAccept) {
                    $user->updateOrganization($invitedOrganization);
                    model("z_organization")->deactivateInvite($invite["id"]);

                    return $res->success();
                }

                return $res->render("administration/organization_invitation", [
                    "invite" => $invite,
                    // name() is typed string, an organization may have none
                    "organizationName" => $invitedOrganization->getField("name"),
                ], "layout/min_layout.php");
            }

            $invites = [];
            if(!is_null($organization) && $req->checkPermission("z.organization.invite", true)) {
                foreach(model("z_organization")->getInvitesByOrganization($organization) as $invite) {
                    // Expired invites are not listed
                    $invite["expires_at"] = strtotime($invite["created"]) + self::ORGANIZATION_INVITE_LIFETIME;
                    if($invite["expires_at"] >= time()) $invites[] = $invite;
                }
            }

            $members = [];
            $roleFood = [];
            if(!is_null($organization) && $req->checkPermission("z.organization.roles", true)) {
                foreach(model("z_organization")->getAssignableRoles() as $role) {
                    $roleFood[] = ["value" => $role["id"], "text" => $role["name"]];
                }

                $heldRoleIds = [];
                foreach(model("z_organization")->getAssignedRoles($organization) as $assignment) {
                    $heldRoleIds[$assignment["user"]][] = $assignment["role"];
                }

                foreach($organization->getUsers() as $member) {
                    $members[] = [
                        "id" => $member->id(),
                        // email() is typed string, an account may have none
                        "email" => $member->getField("email"),
                        "roles" => $heldRoleIds[$member->id()] ?? [],
                    ];
                }
            }

            return $res->render("administration/organization.php", [
                "organizationName" => $organization?->getField("name"),
                "invites" => $invites,
                "members" => $members,
                "roleFood" => $roleFood,
            ]);
        }

        // Action for the role configuration page
        public function action_roles(Request $req, Response $res) {
            $req->checkPermission("admin.roles.list");

            $roleId = $req->getParameters(0, 1);

            if($req->isAction("create")) {
                $req->checkPermission("admin.roles.create");
                $rid = $req->getModel("z_user")->createRole();
                return $res->generateRest(["roleId" => $rid]);
            }

            if(empty($roleId) && $roleId !== "0") {
                return $res->render("administration/role_select.php", [
                    "roles" => $req->getModel("z_general")->getTableWhere("z_role", "*", "active = ? AND is_group = 0", "i", [1]),
                ]);
            }

            $req->checkPermission("admin.roles.edit");
            $role = $req->getModel("z_general")->getTableWhere("z_role", "*", "id = ? AND is_group = 0", "i", [$roleId])[0];

            if(!$role) return $res->error("Role not found");

            if($req->isAction("delete")) {
                $req->checkPermission("admin.roles.delete");
                $req->getModel("z_user")->deactivateRole($roleId);
                return $res->success();
            }

            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("name"))->required()->length(3, 100),
                    (new FormField("is_org_assignable"))->required()->in(["0", "1"]),
                ]);
                $subformResult = $req->validateCED("permissions", [
                    (new FormField("name"))->required()->length(3, 100),
                ]);

                if($subformResult->hasErrors || $formResult->hasErrors) {
                    return $res->formErrors($subformResult->errors, $formResult->errors);
                }

                $res->doCED("z_role_permission", $subformResult, ["role" => $roleId]);
                $res->updateDatabase("z_role", "id", "i", $roleId, $formResult);
                return $res->success();
            }

            return $res->render("administration/roles.php", [
                "name" => $role["name"],
                "isOrgAssignable" => (bool) $role["is_org_assignable"],
                "permissions" => $this->makeCEDFood($req->getModel("z_general")->getTableWhere("z_role_permission", "*", "active = 1 AND role = ?", "i", [$roleId]), ["name"]),
            ]);
        }

        public function action_database(Request $req, Response $res) {
            $req->checkPermission("admin.database");

            $table = $req->getParameters(0, 1);
            if(empty($table)) {
                return $res->render("database/tables.php", [
                    "status" => $req->getModel("z_adminDashboard")->getTableStatus(),
                ]);
            }

            $task = $req->getParameters(1, 1);

            // Find the current page
            $page = 1;
            if("page" == $task) $page = max(1, (int) $req->getParameters(2, 1));
            if("csv" == $task) $page = null;

            $table = $req->getModel("z_adminDashboard")->getRowStatus($table, $page);

            if("csv" == $task) {
                return $req->getModel("z_adminDashboard")->exportToCsv($table);
            }

            $paginationStart = max(1, min($page - 2, $table["totalPages"] - 4));
            $paginationEnd = min($table["totalPages"], $paginationStart + 4);

            return $res->render("database/rows.php", [
                "wideContent" => true,
                "pageLink" => "$table[name]/page/",
                "table" => $table,
                "page" => $page,
                "paginationStart" => $paginationStart,
                "paginationEnd" => $paginationEnd,
                "paginationNext" => min($table["totalPages"], $page + 1),
                "paginationLast" => max(1, $page - 1),
                "totalPages" => $table["totalPages"],
            ]);
        }
    }

?>
