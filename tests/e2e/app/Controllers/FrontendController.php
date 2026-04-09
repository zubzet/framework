<?php

    use ZubZet\Framework\Authentication\Permission\User;

    class FrontendController extends z_controller {

        public function action_backendrequest(Request $req, Response $res) {
            if ($req->isAction("add")) {
                $number1 = $req->getPost("number1");
                $number2 = $req->getPost("number2");

                return $res->success([
                    "response" => $number1 + $number2
                ]);
            }

            if ($req->isAction("err")) {
                return $res->error();
            }

            if ($req->isAction("cust")) {
                return $res->generateRest([
                    "response" => "customrest"
                ]);
            }

            if ($req->isAction("custerr")) {
                return $res->generateRestError(403,"customerror");
            }
            return $res->render("frontend/backend");
        }

        public function action_register(Request $req, Response $res) {
            $userData = [];
            foreach(User::all() as $userObj) {
                $userData[] = [
                    "id" => $userObj->id(),
                    "email" => $userObj->email(),
                    "verified" => $userObj->verified(),
                ];
            }

            return $res->render("frontend/register", [
                "users" => $userData
            ]);
        }

        public function action_login(Request $req, Response $res) {
            return $res->render("frontend/login", [
                "userId" =>  $req->getRequestingUser()->userId ?? 0
            ]);
        }
    }
?>