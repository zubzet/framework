<?php 
    class SettingsController {

        public static $permissionLevel = 0;

        private function toNull($str) {
            return (!isset($str) || empty($str) || $str == "" ? null : $str);
        }

        public function action_account($req, $res) {

            //Change password
            if ($req->getParameters(0, 1, "change_password")) {
                $code = $req->getModel("z_login", $req->getZRoot())->addResetCode(
                    $req->getRequestingUser()["id"],
                    $req->getModel("General")->getUniqueRef(),
                    "change"
                );
                $res->rerouteUrl("login/change_password/$code");
            }

            if ($req->getParameters(0, 1, "email")) {
                $res->generateRest([
                    "emailExists" => $req->getModel("Employee")->emailExistsExcludingEmployee(
                        $req->getParameters(1, 1), 
                        $req->getRequestingUser()["id"]
                    )
                ]);
            }

            //Save the settings
            if ($req->getPost("Save", false) !== false) {

                //Validate email
                if(!filter_var($req->getPost("email"), FILTER_VALIDATE_EMAIL)) {
                    die("Email could not be validated. Please contact an administrator.");
                }

                //Make sure the email is unique
                if($req->getModel("Employee")->emailExistsExcludingEmployee(
                    $req->getPost("email"),
                    $req->getRequestingUser()["id"]
                )) die("Email already in use. Please contact an administrator.");

                //Update the account settings
                $req->getModel("Employee")->updateAccountSettings(
                    $req->getRequestingUser()["id"],
                    $req->getPost("email", false),
                    $req->getPost("notifications_skills", false) == "on",
                    $req->getPost("notifications_time", false) == "on",
                    $req->getPost("notifications_pp", false) == "on",
                    $req->getPost("language", 0)
                );
                $req->updateRequestingUser();
            }

            $res->render("settings_account.php", [
                "title" => "Account Settings - Skill-DB ACOPA",
                "mail" => strtolower($req->getRequestingUser()["email"]),
                "notifications_skills" => $req->getRequestingUser()["notificationsEnabled_Skills"] ? 'checked' : '',
                "notifications_time" => $req->getRequestingUser()["notificationsEnabled_Time"] ? 'checked' : '',
                "notifications_pp" => $req->getRequestingUser()["notificationsEnabled_ProfilePicture"] ? 'checked' : '',
                "language_id" => $req->getRequestingUser()["languageId"],
                "ref_save" => $req->getPost("Save", false) !== false,
                "languages" => $req->getModel("General")->getLanguageList(),
                "ref_save_error" => true
            ]);
        }

        public function action_time($req, $res) {

            if ($req->getPost("Save", false) !== false) {
                $req->getModel("TimeTable")->deleteAllTimeTableLinesByEmployeeId($req->getRequestingUser()["id"]);
                foreach($_POST["timeTableLine"] as $row) {
                    $req->getModel("TimeTable")->addTimeRowByEmployeeId($req->getRequestingUser()["id"], $row["date"], $this->toNull($row["start"]), $this->toNull($row["end"]), isset($row["duration"]) ? $row["duration"] : 0);
                }
                
                $res->generateRest([
                    "result" => "success"
                ]);
            }

            $res->render("settings_time.php", [
                "title"=>"Time Settings - Skill-DB ACOPA",
                "timeTable" => $req->getModel("TimeTable")->getTimeTableByEmployeeId($req->getRequestingUser()["id"])
            ]);
        }

        public function action_skills($req, $res) {

            if ($req->getPost("Save", false) !== false) {

                $success = true;

                if (isset($_POST["skill_assignment"])) {
                    foreach($_POST["skill_assignment"] as $skillAssignment) {

                        if ($skillAssignment["change"] == "remove") {
                            $req->getModel("Skill")->deleteAssignmentById($skillAssignment["id"]);
                        } else if ($skillAssignment["change"] == "edit") {
                            $req->getModel("Skill")->editAssignmentById($skillAssignment["id"], $skillAssignment["scaleId"], $skillAssignment["experience"]);
                        } else if ($skillAssignment["change"] == "add") {
                            $req->getModel("Skill")->addAssignment($skillAssignment["skillId"], $skillAssignment["scaleId"], $skillAssignment["experience"], $req->getRequestingUser()["id"]);
                        } else {
                            $success = false;
                        }
                        //ToDo: löscht hier nicht :( (Manchmal?)

                    }
                }
                
                $res->generateRest([
                    "result" => ($success ? "success" : "error")
                ]);

            }

            $res->render("settings_skills.php", [
                "title" => "Skill Settings - Skill-DB ACOPA",
                "skill_categories" => $req->getModel("Skill")->getCategories(),
                "skill_list" => $req->getModel("Skill")->getOccurrences("skillcategory"),
                "skill_assignments" => $req->getModel("Skill")->getAssignmentsByEmployeeId($req->getRequestingUser()["id"]),
                "skill_scales" => $req->getModel("Skill")->getScales()
            ]);
        }
    }

?>