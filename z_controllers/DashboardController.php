<?php 

    class DashboardController {

        public static $permissionLevel = 0;
        
        public function action_index($req, $res) {
            $updates = [
                "skills" => [
                    "update" => $req->getModel("Skill")->getLastUpdateByEmployeeId($req->getRequestingUser()["id"]),
                    "period" => $req->getBooterSettings("skillUpdatePeriod")
                ],
                "time" => [
                    "update" => $req->getModel("Dashboard")->getLastTimeUpdateByEmployeeId($req->getRequestingUser()["id"]),
                    "period" => $req->getBooterSettings("timeUpdatePeriod")
                ],
                "profilePicture" => [
                    "update" => $req->getModel("Dashboard")->getLastProfilePictureUpdateByEmployeeId($req->getRequestingUser()["id"]),
                    "period" => $req->getBooterSettings("profilePictureUpdatePeriod")
                ]
            ];
            array_walk($updates, function(&$item) {
                $item["update"] = strtotime($item["update"]);
                $item["period"] = strtotime("-".$item["period"]);
                if (!$item["update"]) $item["update"] = 0;
            });

            $show_tutorial = !$req->getModel("Dashboard")->getTutorialStatusByEmployeeId($req->getRequestingUser()["id"]);   
            if($req->getRequestingUser()["id_exec"] != $req->getRequestingUser()["id"]) $show_tutorial = false;;

            $res->render("dashboard.php", [
                "employee" => $req->getRequestingUser(),
                "permissionName" => $req->getPermissionNameByLevel($req->getRequestingUser()["permissionLevel"]),
                "date" => date('l \t\h\e jS \of F H:i'),
                "employeeCount" => $req->getModel("Employee")->getCount(),
                "loginCount" => $req->getModel("Dashboard")->getLoginCount(),
                "cvGenerateCount" => $req->getModel("CV")->getCvGenerateCount(),
                "employees" => $req->getModel("Dashboard")->getDashboardEmployeeTable(),
                "permissionLevel" => $req->getRequestingUser()["permissionLevel"],
                "updateNeeded_skills" => $updates["skills"]["update"] < $updates["skills"]["period"],
                "updateNeeded_time" => $updates["time"]["update"] < $updates["skills"]["period"],
                "updateNeeded_profilePicture" => $updates["profilePicture"]["update"] < $updates["skills"]["period"],
                "show_tutorial" => $show_tutorial
            ]);
        }

        public function action_finish_tutorial($req, $res) {
            $req->getModel("Dashboard")->finishTutorialByEmployeeId($req->getRequestingUser()["id"]);
        }

	}

?>