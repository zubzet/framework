<?php 

    class AdminController {

        public static $permissionLevel = 2;
        
        private $categoryList;
        private function checkCategoryId($id) {

            foreach($this->categoryList as $category) {
                if ($category["id"] == $id) return true;             
            }

            return false;
        }

        private function filterURLData($str) {
            $str = str_replace("%3A", ":", $str);
            $str = str_replace("%20", " ", $str);
            return $str;
        }

        public function action_cfg_skills($req, $res) {
            if ($req->getPost("Save", false) !== false) {

                $success = true;
                
                $this->categoryList = $req->getModel("Skill")->getCategories();

                foreach($_POST["skill"] as $skill) {

                    if (in_array($skill["change"], ["edit", "add"])) {
                        if (!$this->checkCategoryId($skill["categoryId"])) {
                            $success = false;
                            continue;
                        }
                    }

                    if ($skill["change"] == "remove") {
                        $req->getModel("Skill")->deleteById($skill["id"]);
                    } else if ($skill["change"] == "edit") {
                        $req->getModel("Skill")->editById($skill["id"], $skill["name"], $skill["categoryId"]);
                    } else if ($skill["change"] == "add") {
                        $req->getModel("Skill")->add($skill["name"], $skill["categoryId"]);
                    } else {
                        $success = false;
                    }
                }
                
                $res->generateRest([
                    "result" => ($success ? "success" : "error")
                ]);

            }

            $res->render("admin_config_skills.php", [
                "title" => "Skills Config - Skill-DB ACOPA",
                "categories" => $req->getModel("Skill")->getCategories(),
                "skills" => $req->getModel("Skill")->getOccurrences()
            ]);
        }

        public function action_cfg_instance($req, $res) {

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

            $res->render("admin_config_instance.php", [
                "title" => "Instance Config - Skill-DB ACOPA",
                "configured_fields" => $req->getBooterSettings(),
                "ref_save" => $req->getPost("Save", false) !== false
            ]);
        }

        public function action_cfg_company($req, $res) {

            if ($req->getPost("Save", false) !== false) {
                $req->getModel("Company")->deleteAll();
                $req->getModel("Company")->add(
                    $req->getPost("name"),
                    $req->getPost("email"), 
                    $req->getPost("addr_country"), 
                    $req->getPost("addr_state"), 
                    $req->getPost("addr_city"), 
                    $req->getPost("addr_zip"), 
                    $req->getPost("addr_street"), 
                    $req->getPost("addr_street_number"), 
                    $req->getPost("web"), 
                    $req->getPost("phone"), 
                    $req->getPost("mobile_phone"), 
                    $req->getPost("fax")
                );
            }

            $res->render("admin_config_company.php", [
                "ref_save" => $req->getPost("Save", false) !== false,
                "company" => $req->getModel("Company")->getInfo(),
                "countries" => $req->getModel("General")->getCountryList()
            ]);
        }

        public function action_unique($req, $res) {

            if ($req->getParameters(0, 1) == "email") {
                $email = base64_decode($req->getParameters(1, 1));
                $res->generateRest([
                    "result" => ($req->getModel("Employee")->checkUniqueEmail($email) ? "success" : "error")
                ]);
            }

            if ($req->getParameters(0, 1) == "name") {
                $firstName = $req->getParameters(1, 1);
                $lastName = $req->getParameters(2, 1);
                $res->generateRest([
                    "result" => ($req->getModel("Employee")->checkUniqueFirstNamelastName($firstName, $lastName) ? "success" : "error")
                ]);
            }

            //Make sure the email is unique
            if ($req->getParameters(0, 1) == "email_ex") {
                $res->generateRest([
                    "result" => ($req->getModel("Employee")->emailExistsExcludingEmployee(
                        base64_decode($req->getParameters(1, 1)), //email
                        $req->getParameters(2, 1)                 //id
                    ) ? "error" : "success")
                ]);
            }

            //Unique name
            if ($req->getParameters(0, 1) == "name_ex") {
                $res->generateRest([
                    "result" => ($req->getModel("Employee")->nameLastNamelExistsExcludingEmployee(
                        $req->getParameters(1, 1), //name
                        $req->getParameters(2, 1), //lastName
                        $req->getParameters(3, 1)  //id
                    ) ? "success" : "error")
                ]);
            }

        }

        public function action_add_employee($req, $res) {

            if ($req->getParameters(0, 1) == "ajax") {

                $user_email = $req->getModel("z_login", $req->getZRoot())->findAccount($req->getPost("email"));
                $user_name = $req->getModel("z_login", $req->getZRoot())->findAccount($req->getPost("firstName").".".$req->getPost("name"));

                if (isset($user_email["id"])) $res->generateRestError("AEE", "Email is already in use.");
                if (!filter_var($req->getPost("email"), FILTER_VALIDATE_EMAIL)) $res->generateRestError("AEV", "Email could not be validated. Please contact an administrator.");
                if (isset($user_name["id"])) $res->generateRestError("AEN", "Name is already in use.");

                //Add account
                $newEmployeeId = $req->getModel("Employee")->add(
                    $req->getPost("name"),
                    $req->getPost("firstName"),
                    $req->getPost("email"),
                    $req->getPost("tag"),
                    $req->getPost("permissionLevel"),
                    $req->getPost("language")
                );

                $code = $req->getModel("z_login", $req->getZRoot())->addResetCode(
                    $newEmployeeId,
                    $req->getModel("General")->getUniqueRef(),
                    "create"
                );
                
                $register_url = $req->getBooterSettings("host") . $req->getRootFolder() . "login/create_password/$code/";

               $res->sendEmailToUser(
                    $newEmployeeId,
                    "SKDB Registration",
                    "email_register_invite.php", 
                    [
                        "name" => $req->getPost("name"),
                        "firstName" => $req->getPost("firstName"),
                        "register_link" => $register_url
                    ]
                );

                $res->generateRest(["result" => "success"]);
                
            }

            $res->render("admin_add_employee.php", [
                "title" => "Add Employee - Skill-DB ACOPA", 
                "ref_save" => $req->getPost("Save", false) !== false,
                "languages" => $req->getModel("General")->getLanguageList(),
                "ref_save_user" => $req->getPost("firstName") . " ". $req->getPost("name"),
                "tags" => $req->getModel("Employee")->getTagList(),
                "permissionNames" => $req->getModel("Employee")->getPermissionLevelNames()
            ]);
            
        }

        function action_edit_employee($req, $res) {

            if ($req->getPost("Select", false) !== false) {

                $employeeId = $req->getPost("employee_id", false);
                if ($employeeId) {
                    $res->generateRest([
                        "data" => [
                            $req->getModel("Employee")->getMetaById($employeeId)
                        ]
                    ]);
                } else {
                    $res->generateRestError("acee", "Employee data could not be recieved.");
                }
            
            } else if ($req->getParameters(0, 1) == "ajax") {

                $user_email = $req->getModel("Employee")->emailExistsExcludingEmployee($req->getPost("email"), $req->getPost("employeeId", -1));
                $user_name = $req->getModel("Employee")->nameLastNamelExistsExcludingEmployee($req->getPost("name"), $req->getPost("lastName"), $req->getPost("employeeId", -1));

                if ($user_email) $res->generateRestError("EEE", "Email is already in use."); // ! negated because CNT is checked for > 0
                if (!filter_var($req->getPost("email"), FILTER_VALIDATE_EMAIL)) $res->generateRestError("EEV", "Email could not be validated. Please contact an administrator.");
                if (!$user_name) $res->generateRestError("EEN", "Name is already in use.");

                $req->getModel("Employee")->updateMetaById(
                    $req->getPost("name", null),
                    $req->getPost("firstName", null),
                    $req->getPost("email", null), 
                    $req->getPost("tag", null), 
                    $req->getPost("permissionLevel", null), 
                    $req->getPost("languageId", null), 
                    $req->getPost("employeeId", null)
                );

                $res->generateRest(["result" => "success"]);

            }

            $res->render("admin_edit_employee.php", [
                "title" => "Edit Employee - Skill-DB ACOPA", 
                "ref_save" => $req->getPost("Save", false) !== false,
                "ref_save_userId" => $req->getPost("employee_id", false),
                "ref_save_user" => $req->getPost("firstName") . " ". $req->getPost("name"),
                "languages" => $req->getModel("General")->getLanguageList(),
                "employees" => $req->getModel("Employee")->getMeta(),
                "tags" => $req->getModel("Employee")->getTagList(),
                "permissionNames" => $req->getModel("Employee")->getPermissionLevelNames()
            ]);

        }

        function action_login_as_employee($req, $res) {

            if ($req->getPost("Save", false) !== false) {
                $res->loginAs($req->getPost("employee_id"), $req->getRequestingUser()["id_exec"]);
                $res->rerouteUrl();
            }

            $res->render("admin_login_as_employee.php", [
                "title" => "Login as Employee - Skill-DB ACOPA", 
                "ref_save" => $req->getPost("Save", false) !== false,
                "ref_save_userId" => $req->getPost("employee_id", false),
                "ref_save_user" => $req->getPost("firstName") . " ". $req->getPost("name"),
                "employees" => $req->getModel("Employee")->getMeta()
            ]);
        }

        function action_log_statistics($req, $res) {

            if ($req->getParameters(0, 1, "ajax")) {

                $format = $req->getParameters(4, 1);
                $data = $req->getModel("Statistics")->getLogTableByCategories(
                    $this->filterURLData($req->getParameters(2, 1)),
                    $this->filterURLData($req->getParameters(3, 1)),
                    explode(",", $req->getParameters(1, 1))
                );

                if ($format == "json") {

                    $res->generateRest([
                        "data" => $data
                    ]);

                } elseif ($format == "csv") { //csv

                    header("Content-type: text/csv");
                    header("Content-Disposition: attachment; filename=SKDB_Log_".date("D_M_d_Y_G:i").".csv");
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
                    header("Content-Disposition: inline; filename=SKDB_Log_".date("D_M_d_Y_G:i").".txt");

                    foreach ($data as $i => $row) {
                        echo $row["created"] . ": ";
                        echo $row["text"]. ": ";
                        echo $row["text"];
                        echo "\n\r";
                    }
                    exit;

                }
            }

            $res->render("admin_log_statistics.php", [
                "log_categories" => $req->getModel("Statistics")->getLogCategories()
            ]);
        }

        function action_availability_overview($req, $res) {

            if ($req->getParameters(0, 1, "ajax")) {
                $res->generateRest([
                    "data" => $req->getModel("TimeTable")->getTimeTableRowsByEmployyeeId(
                        $this->filterURLData($req->getParameters(1, 1)),
                        $this->filterURLData($req->getParameters(2, 1)),
                        explode(",", $req->getParameters(3, 1))
                    )
                ]);
            }
            
            $employeeList = $req->getModel("Employee")->getMeta();
            foreach ($employeeList as $i => $employee) {
                $employeeList[$i]["full_name"] = $employee["firstName"] ." ". $employee["name"];
            }

            $auto_complete_employee_list = [];
            foreach ($employeeList as $i => $employee) {
                $auto_complete_employee = $employee;
                $auto_complete_employee["full_name"] = $employee["name"] ." ". $employee["firstName"];
                $auto_complete_employee_list[] = $auto_complete_employee;
            }

            $res->render("admin_availability_overview.php", [
                "employees" => $employeeList,
                "auto_complete_employees" => $auto_complete_employee_list
            ]);
        }   

    }

?>