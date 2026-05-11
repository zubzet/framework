<?php

    use ZubZet\Framework\Logger\LogEventType;
    use ZubZet\Framework\Logger\Logger;
    use ZubZet\Framework\Maintenance\MaintenanceHandler;
    use ZubZet\Framework\Message\Response;

    /**
     * The ZController contains actions for the admin dashboard / panel
     */
    class ZController extends z_controller {

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
                "roles" => $this->makeFood($req->getModel("z_general")->getTableWhere("z_role", "*", "active = ?", "i", [1]), "id", "name"),
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

            $res->loginAs($userId, $req->getRequestingUser()->execUserId);
            return $res->rerouteUrl();
        }


        public function action_groups(Request $req, Response $res) {
            $req->checkPermission("admin.groups.list");

            return $res->render("administration/groups.php", [
                "groups" => model("z_general")->getGroups(),
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
