<?php

    class MultipleFormController extends z_controller {

        public function action_multiple(Request $req, Response $res) {
            if($req->hasFormData("billing")) {
                $formResult = $req->validateForm([
                    (new FormField("billing_value"))
                        ->required(),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success([
                    "form" => "billing",
                    "billing_value" => $req->getPost("billing_value"),
                    "shipping_value" => $req->getPost("shipping_value"),
                ]);
            }

            if($req->hasFormData("shipping")) {
                $formResult = $req->validateForm([
                    (new FormField("shipping_value")),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success([
                    "form" => "shipping",
                    "billing_value" => $req->getPost("billing_value"),
                    "shipping_value" => $req->getPost("shipping_value"),
                ]);
            }

            return $res->render("multipleForm/multiple");
        }

        public function action_named(Request $req, Response $res) {
            if($req->hasFormData("named-action")) {
                return $res->success([
                    "formAction" => $req->getPost("formAction"),
                ]);
            }

            return $res->render("multipleForm/named");
        }

        public function action_domFallback(Request $req, Response $res) {
            if($req->hasFormData("fallback-form")) {
                return $res->success([
                    "formAction" => $req->getPost("formAction"),
                ]);
            }

            return $res->render("multipleForm/domFallback");
        }

        public function action_unnamed(Request $req, Response $res) {
            if($req->hasFormData()) {
                return $res->success([
                    "formAction" => $req->getPost("formAction"),
                ]);
            }

            return $res->render("multipleForm/unnamed");
        }

        public function action_probe(Request $req, Response $res) {
            return $res->json([
                "any" => $req->hasFormData(),
                "named" => $req->hasFormData("target-action"),
            ]);
        }

    }
?>
