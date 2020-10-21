<?php 
    /**
     * This file contains the ZController
     * 
     * Permissions used here:
     *  admin.panel
     *  admin.user.list
     *  admin.user.add
     *  admin.user.edit
     *  admin.roles.list
     *  admin.roles.create
     *  admin.roles.edit
     *  admin.roles.delete
     *  admin.log
     *  admin.su
     *  admin.danger.cfg
     */

    /**
     * The ZController contains actions for the admin dashboard / panel
     */
    class ZController extends z_controller {

        /**
         * Serves an empty index page with the admin layout
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_index($req, $res) {
            $req->checkPermission("admin.panel");
            $res->render("z_empty.php", [
                
            ], "layout/z_admin_layout.php");
        }

        /**
         * Action for the instance configuration
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_cfg_instance($req, $res) {
            $req->checkPermission("admin.danger.cfg");

            if ($req->getPost("Save", false) !== false) {
                unset($_POST["Save"]);
                file_put_contents($req->getConfigFile(), "");
                foreach ($_POST as $name => $setting) {
                    file_put_contents(
                        $req->getConfigFile(), 
                        $name . " = " . $setting . "\n",
                        FILE_APPEND
                    );
                }
                header("location: ".$req->getRootFolder());
                exit;
            }

            $res->render("z_instance.php", [
                "title" => "Instance Config",
                "configured_fields" => $req->getBooterSettings(),
                "ref_save" => $req->getPost("Save", false) !== false
            ], "layout/z_admin_layout.php");
        }

        /**
         * Action for adding a user
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        public function action_add_user($req, $res) {
            $req->checkPermission("admin.user.add");

            if ($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("email"))      -> required() -> filter(FILTER_VALIDATE_EMAIL) -> unique("z_user", "email"),
                    (new FormField("languageId")) -> required() -> exists("z_language", "id")
                ]);

                if ($formResult->hasErrors) {
                    $res->formErrors($formResult->errors);
                } else {
                    require_once $req->getZRoot().'z_libs/passwordHandler.php';
                    if ($req->getModel("z_user", $res->getZRoot())->add(
                        $req->getPost("email"),
                        $req->getPost("languageId"),
                        $req->getPost("password")
                    ) == false) {
                        $res->error();
                    } else {
                        $res->success();
                    }
                }
            }

            $res->render("z_add_user.php", [
                "title" => "Add user",
                "languages" => $this->makeFood($req->getModel("z_general")->getLanguageList(), "id", "name")
            ], "layout/z_admin_layout.php");
            
        }

        /**
         * Action for editing a user
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        function action_edit_user($req, $res) {
            $req->checkPermission("admin.user.list");

            $userId = $req->getParameters(0, 1);
            if (($userId === '0') || !empty($userId)) {
                $req->checkPermission("admin.user.edit");
                $user = $req->getModel("z_user")->getUserById($userId);
                $email = $user["email"];

                if ($req->hasFormData()) {
                    $formResult = $req->validateForm([
                        (new FormField("email"))        -> required() -> filter(FILTER_VALIDATE_EMAIL) -> unique("z_user", "email", "id", $userId),
                        (new FormField("languageId"))   -> required() -> exists("z_language", "id")
                    ]);

                    $subformResult = $req->validateCED("roles", [
                        (new FormField("role")) -> required() -> exists("z_role", "id")
                    ]);
    
                    if ($formResult->hasErrors || $subformResult->hasErrors) {
                        $res->formErrors($formResult->errors, $subformResult->errors);
                    } else {
                        $res->doCED("z_user_role", $subformResult, ["user" => $userId]);
                        $res->updateDatabase("z_user", "id", "i", $userId, $formResult);
                        $res->log("user", "User $email changed", 0);
                        $res->success();
                    }
                }

                $res->render("z_edit_user.php", [
                    "title" => "Edit user", 
                    "languages" => $this->makeFood($req->getModel("z_general")->getLanguageList(), "id", "name"),
                    "users" => $this->makeFood($req->getModel("z_user")->getUserList(), "id", "email"),
                    "roles" => $this->makeFood($req->getModel("z_general")->getTableWhere("z_role", "*", "active = ?", "i", [1]), "id", "name"),
                    "user_roles" => $this->makeCEDFood($req->getModel("z_user")->getRoles($userId), ["role"]),
                    "result" => "success",
                    "email" => $user["email"],
                    "language" => $user["languageId"],
                    "userId" => $userId
                ], "layout/z_admin_layout.php");
            } else {
                $res->render("z_user_select.php", [
                    "users" => $req->getModel("z_user")->getUserList()
                ], "layout/z_admin_layout.php");
            }
        }

        /**
         * Action for logging in as someone else
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        function action_login_as($req, $res) {
            $req->checkPermission("admin.su");

            $userId = $req->getParameters(0, 1);
            if (($userId === '0') || !empty($userId)) {
                $res->loginAs($userId, $req->getRequestingUser()->execUserId);
                $res->rerouteUrl();
            }
        }

        /**
         * Action for the role configuration page
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        function action_roles($req, $res) {
            $req->checkPermission("admin.roles.list");

            $roleId = $req->getParameters(0, 1);

            if ($req->isAction("create")) {
                $req->checkPermission("admin.roles.create");
                $rid = $req->getModel("z_user")->createRole();
                $res->generateRest(["roleId" => $rid]);
            }

            if (!empty($roleId) || $roleId === "0") {
                $req->checkPermission("admin.roles.edit");
                $role = $req->getModel("z_general")->getTableWhere("z_role", "*", "id = ?", "i", [$roleId])[0];

                if ($req->isAction("delete")) {
                    $req->checkPermission("admin.roles.delete");
                    $rid = $req->getModel("z_user")->deactivateRole($roleId);
                    $res->success();
                }

                if ($req->hasFormData()) {

                    $formResult = $req->validateForm([
                        (new FormField("name")) -> required() -> length(3, 100)
                    ]);
                    $subformResult = $req->validateCED("permissions", [
                        (new FormField("name")) -> required() -> length(3, 100)
                    ]);
                    if ($subformResult->hasErrors || $formResult->hasErrors) {
                        $res->formErrors($subformResult->errors, $formResult->errors);
                    } else {
                        $res->doCED("z_role_permission", $subformResult, ["role" => $roleId]);
                        $res->updateDatabase("z_role", "id", "i", $roleId, $formResult);
                        $res->success();
                    }
                }

                $res->render("z_roles.php", [
                    "name" => $role["name"],
                    "permissions" => $this->makeCEDFood($req->getModel("z_general")->getTableWhere("z_role_permission", "*", "active = 1 AND role = ?", "i", [$roleId]), ["name"])
                ], "layout/z_admin_layout.php");
            } else {
                $res->render("z_role_select.php", [
                    "roles" => $req->getModel("z_general")->getTableWhere("z_role", "*", "active = ?", "i", [1])
                ], "layout/z_admin_layout.php");
            }

        }

        /**
         * Action for seeing the logs
         * 
         * @param Request $req The request object
         * @param Response $res The response object
         */
        function action_log($req, $res) {
            $req->checkPermission("admin.log");

            if ($req->getParameters(0, 1, "ajax")) {
                $format = $req->getParameters(4, 1);
                $data = $req->getModel("z_statistics", $res->getZRoot())->getLogTableByCategories(
                    urldecode($req->getParameters(2, 1)),
                    urldecode($req->getParameters(3, 1)),
                    explode(",", $req->getParameters(1, 1))
                );

                if ($format == "json") {

                    $res->generateRest([
                        "data" => $data
                    ]);

                } elseif ($format == "csv") { //csv

                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=ZIT_Log_".date("D_M_d_Y_G:i").".csv");
                    header("Pragma: no-cache");
                    header("Expires: 0");

                    foreach ($data as $i => $row) {
                        foreach ($row as $j => $column) {
                            str_replace(";", "%3B",$data[$i][$j]);
                        }
                    }
                    foreach ($data[0] as $key => $row) {
                        echo $key.";";
                    }
                    echo "\n";
                    foreach ($data as $row) {
                        foreach ($row as $column) {
                            echo $column.";";
                        }
                        echo "\n";
                    }
                    exit;

                } else { //txt - might need a name

                    header('Content-Type:text/plain');
                    header("Content-Disposition: inline; filename=ZIT_Log_".date("D_M_d_Y_G:i").".txt");

                    foreach ($data as $i => $row) {
                        echo $row["created"] . ": ";
                        echo $row["text"]. ": ";
                        echo $row["text"];
                        echo "\n\r";
                    }
                    exit;

                }
            }

            $res->render("z_log.php", [
                "log_categories" => $req->getModel("z_statistics", $res->getZRoot())->getLogCategories()
            ], "layout/z_admin_layout.php");
        }
    }

?>