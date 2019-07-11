<?php

    class CronjobController {

            public function action_index($req, $res) {
                //Remove the max executin time
                set_time_limit(-1);

                //authenticate the request
                if($req->getGet("cronjob_auth_token") === $req->getBooterSettings("cronjob_auth_token"))  {
                    
                    //Run all the email checks
                    $this->update_time($req, $res);
                    $this->update_skill($req, $res);
                    $this->update_profile_picture($req, $res);

                } else {

                    //Show an error
                    die("Wrong or no auth token!");

                }
                  
            }

            public function update_time($req, $res) {

                $period = strtotime("-".$req->getBooterSettings("timeUpdatePeriod"));
                $users = [];
                foreach($req->getModel("TimeTable")->getLastUpdates() as $row) {
                    if(($row["last_update"] === NULL || strtotime($row["last_update"]) < $period) && $row["NE"] === 1) {
                        $users[] = $row["id"];
                        continue;
                    }
                }

                $link = $req->getBooterSettings("host") . $req->getRootFolder() . "settings/time";

                $this->mass_send_emails($res, $req, $users, [
                    "en" => "Time Update Reminder",
                    "DE_Formal" => "Zeiten Aktualitäts Errinerung"
                ], "email_update_time.php", [
                    "link" => $link
                ]);

            }

            public function update_skill($req, $res) {

                $period = strtotime("-".$req->getBooterSettings("skillUpdatePeriod"));
                $users = [];
                foreach($req->getModel("Skill")->getLastUpdates() as $row) {
                    if(($row["last_update"] === NULL || strtotime($row["last_update"]) < $period) && $row["NE"] === 1) {
                        $users[] = $row["id"];
                        continue;
                    }
                }

                $link = $req->getBooterSettings("host") . $req->getRootFolder() . "settings/skills";

                $this->mass_send_emails($res, $req, $users, [
                    "en" => "Skill Update Reminder",
                    "DE_Formal" => "Skill Aktualitäts Errinerung"
                ], "email_update_skills.php", [
                    "link" => $link
                ]);

            }

            public function update_profile_picture($req, $res) {

                $period = strtotime("-".$req->getBooterSettings("profilePictureUpdatePeriod"));
                $users = [];
                foreach($req->getModel("CV")->getLastProfilePictureUpdates() as $row) {
                    if(($row["last_update"] === NULL || strtotime($row["last_update"]) < $period) && $row["NE"] === 1) {
                        $users[] = $row["id"];
                        continue;
                    }
                }

                $link = $req->getBooterSettings("host") . $req->getRootFolder() . "cv/portrait";

                $this->mass_send_emails($res, $req, $users, [
                    "en" => "Profile Picture Update Reminder",
                    "DE_Formal" => "Profilbild Aktualitäts Errinerung"
                ], "email_update_picture.php", [
                    "link" => $link
                ]);

            }

            //to-do: better batching
            //Cannot redeclare body() (previously declared in D:\XAMPP\htdocs\skdb-application\z_views\email_update_skills.php:1) in <b>D:\XAMPP\htdocs\skdb-application\z_views\email_update_skills.php</b> on line <b>23</b><br />
            private function mass_send_emails($res, $req, array $user_ids, $subject, $document, $options) {
                foreach ($user_ids as $id) {
                    $meta = $req->getModel("user")->getMetaById($id);
                    $res->sendEmailToUser($id, $subject, $document, $options);
                }
            }

    }

?>