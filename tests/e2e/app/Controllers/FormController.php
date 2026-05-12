<?php

    class FormController extends z_controller {

        public function action_interactions(Request $req, Response $res) {
            if($req->hasFormData()) {
                // None of these fields carry validation rules, so a form
                // submission cannot produce errors here — no formErrors
                // fallback is needed.
                $req->validateForm([
                    (new FormField("field_a")),
                    (new FormField("field_b")),
                    (new FormField("field_c")),
                    (new FormField("field_select")),
                    (new FormField("field_default")),
                    (new FormField("field_select_default")),
                ]);

                return $res->success();
            }

            return $res->render("form/interactions");
        }

        // Probe for the integer() and exists() validation rules. The existing
        // form-fixture controllers don't exercise these. Validation runs on
        // every request (no GET-vs-POST split), and the result is emitted as
        // JSON for cy.request to inspect.
        public function action_validateIntegerExists(Request $req, Response $res) {
            $formResult = $req->validateForm([
                (new FormField("ints"))
                    ->integer(),
                // Use seeded z_role table; "fwapi_KnownRole" exists, the
                // failure case will reference a non-existent value.
                (new FormField("role_name"))
                    ->exists("z_role", "name"),
            ]);

            echo json_encode([
                'hasErrors' => (bool)$formResult->hasErrors,
                'errors'    => $formResult->errors,
            ]);
        }

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
                    // Exercises the ignoreField branch of the unique rule:
                    // the seeded row WHERE value="UniqueText" is excluded
                    // from the uniqueness check, so submitting "UniqueText"
                    // is treated as not-a-duplicate.
                    (new FormField("field_text_unique_ignore"))
                        ->unique("duplicate", "value", "value", "UniqueText"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationText");
        }

        public function action_validationRegex(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    // Letters and spaces only.
                    (new FormField("field_regex"))
                        ->regex("/[A-Za-z ]/"),
                    // Letters and spaces, with `-` and `!` allowed via the
                    // exceptions list (str_replace'd out before the regex check).
                    (new FormField("field_regex_exceptions"))
                        ->regex("/[A-Za-z ]/", ["-", "!"]),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationRegex");
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

                    // @codeCoverageIgnoreStart
                    // FormModel::uploadFile's failure modes (null/empty file,
                    // move_uploaded_file rejecting a fabricated tmp_name) are
                    // exercised directly by action_probeUploadFileEmpty /
                    // action_probeUploadFileMoveFails. From the public form
                    // path the FormField rule rejects empty/missing files
                    // before uploadFile is called, so the false branch here
                    // is unreachable in normal flow but kept as defensive code.
                    if(!$req->getModel("Form")->uploadFile(
                        $req->getFile("file"),
                        "uploads/",
                        $res->getZRoot()
                    )) {
                        return $res->error();
                    }
                    // @codeCoverageIgnoreEnd
                    return $res->success();
                }

                if($req->getParameters(0, 1, "formUpload")) {
                    $formResult = $req->validateForm([
                        (new FormField("file"))
                            ->required()
                            ->file(262144, ["pdf"]),
                    ]);

                    if($formResult->hasErrors) {
                        return $res->formErrors($formResult->errors);
                    }

                    return $res->insertDatabase(
                        "media",
                        $formResult
                    );
                }


                // Upload Validation
                $upload = $res->upload();
                if ($upload->upload(
                    $req->getFile("file"),
                    "uploads/",
                    262144, //250 KB
                    ["pdf"]
                )) {
                    return $res->error();
                }

                return $res->success();
            }

            return $res->render("form/validationFile",[
                "files" => $req->getModel("Form")->getUploadedFiles(),
                "media" => $req->getModel("Form")->getMediaFiles(),
            ]);
        }

        // Probes the early-return branches of FormModel::uploadFile that
        // the public /Form/validationFile/form path cannot reach (the file
        // FormField rule rejects empty/missing files before uploadFile is
        // called). Used by form/file.cy.js.
        public function action_probeUploadFileEmpty(Request $req, Response $res) {
            $result = $req->getModel("Form")->uploadFile(
                null,
                "uploads/",
                $res->getZRoot(),
            );
            return $res->json($result === false);
        }

        public function action_probeUploadFileMoveFails(Request $req, Response $res) {
            // move_uploaded_file() rejects any tmp_name that is not in
            // $_FILES (security check). Calling it with a fabricated path
            // returns false, exercising FormModel::uploadFile's second
            // early-return branch. Requires showErrors=0 so the emitted
            // E_WARNING is not promoted to a fatal ErrorException.
            $result = $req->getModel("Form")->uploadFile(
                [
                    "name" => "fake.pdf",
                    "tmp_name" => "/tmp/this-was-not-uploaded-via-http",
                    "type" => "application/pdf",
                    "size" => 100,
                ],
                "uploads/",
                $res->getZRoot(),
            );
            return $res->json($result === false);
        }

    }
?>