<?php

    class FormController extends z_controller {

        public function action_validationText(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_text")),
                    (new FormField("field_text_required"))
                        ->required(),
                    (new FormField("field_text_length"))
                        ->length(5, 20),
                    (new FormField("field_text_unique"))
                        ->unique("duplicate", "value"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationText");
        }

        public function action_validationNumber(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_number"))
                        ->filter(FILTER_VALIDATE_INT),
                    (new FormField("field_number_required"))
                        ->filter(FILTER_VALIDATE_INT)
                        ->required(),
                    (new FormField("field_number_range"))
                        ->filter(FILTER_VALIDATE_INT)
                        ->range(5, 30),
                    (new FormField("field_number_unique"))
                        ->filter(FILTER_VALIDATE_INT)
                        ->unique("duplicate", "value"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationNumber");
        }

        public function action_validationEmail(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_email"))
                        ->filter(FILTER_VALIDATE_EMAIL),
                    (new FormField("field_email_required"))
                        ->filter(FILTER_VALIDATE_EMAIL)
                        ->required(),
                    (new FormField("field_email_length"))
                        ->filter(FILTER_VALIDATE_EMAIL)
                        ->length(10, 50),
                    (new FormField("field_email_unique"))
                        ->filter(FILTER_VALIDATE_EMAIL)
                        ->unique("duplicate", "value"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationEmail");
        }

        public function action_validationUrl(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_url"))
                        ->filter(FILTER_VALIDATE_URL),
                    (new FormField("field_url_required"))
                        ->filter(FILTER_VALIDATE_URL)
                        ->required(),
                    (new FormField("field_url_length"))
                        ->filter(FILTER_VALIDATE_URL)
                        ->length(15, 50),
                    (new FormField("field_url_unique"))
                        ->filter(FILTER_VALIDATE_URL)
                        ->unique("duplicate", "value"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationUrl");
        }

        public function action_validationDate(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_date"))
                        ->date(),
                    (new FormField("field_date_required"))
                        ->date()
                        ->required(),
                    (new FormField("field_date_length"))
                        ->date(),
                    (new FormField("field_date_unique"))
                        ->date()
                        ->unique("duplicate", "value"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationDate");
        }

        public function action_validationFile(Request $req, Response $res) {
            if($req->hasFormData()) {

                // Form Validation
                if($req->getParameters(0, 1, "form")) {
                    $formResult = $req->validateForm([
                        (new FormField("file"))
                            ->required()
                            ->file(262144, ["pdf"]), //250 KB
                    ]);

                    if($formResult->hasErrors) {
                        return $res->formErrors($formResult->errors);
                    }

                    if(!$req->getModel("Form")->uploadFile(
                        $_FILES["file"],
                        "uploads/",
                        $res->getZRoot()
                    )) {
                        return $res->error();
                    }
                    return $res->success();
                }

                // Upload Validation
                $upload = $res->upload();
                if ($upload->upload(
                    $_FILES["file"],
                    "uploads/",
                    262144, //250 KB
                    ["pdf"]
                )) {
                    return $res->error();
                }

                return $res->success();
            }

            return $res->render("form/validationFile",[
                "files" => $req->getModel("Form")->getUploadedFiles()
            ]);
        }

    }
?>