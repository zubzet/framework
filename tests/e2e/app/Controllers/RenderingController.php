<?php

    class RenderingController extends z_controller {

        public function action_mail(Request $req, Response $res) { //TODO Einrichten
            $res->sendEmail(
                "admin@zierhut-it.de",
                "TestEmail",
                "rendering/testmail.php",
                "de",
                [
                    "custom_value" => "TestValue"
                ],
                "rendering/mail_layout.php"
            );
        }

        public function action_mailuser(Request $req, Response $res) {
            
        }

    }

?>