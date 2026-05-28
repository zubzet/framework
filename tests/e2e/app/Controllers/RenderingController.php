<?php

    class RenderingController extends z_controller {

        // Exercises Response::sendEmail($to, $subject, $document, $lang, $options, $layout).
        // Used by tests/cypress/e2e/template rendering usage/email.cy.js.
        public function action_mail(Request $req, Response $res) {
            $res->sendEmail(
                "admin@zierhut-it.de",
                "TestEmail",
                "rendering/testmail.php",
                "de",
                [
                    "custom_value" => "TestValue",
                ],
                "rendering/mail_layout.php",
            );
        }

        // Exercises Response::sendEmailToUser($userId, $subject, $document, $options, $layout).
        // Targets user 1 (admin@zierhut-it.de seeded in zubzet/1_users.sql).
        public function action_mailuser(Request $req, Response $res) {
            $res->sendEmailToUser(
                1,
                "TestUserEmail",
                "rendering/testmail.php",
                [
                    "custom_value" => "TestUserValue",
                ],
                "rendering/mail_layout.php",
            );
        }

    }

?>
