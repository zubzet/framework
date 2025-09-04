<?php

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
            return $res->render("frontend/register", [
                "users" =>  $req->getModel("Request")->getUsers()
            ]);
        }

        public function action_login(Request $req, Response $res) {
            return $res->render("frontend/login", [
                "userId" =>  $req->getRequestingUser()->userId ?? 0
            ]);
        }
    }
?>