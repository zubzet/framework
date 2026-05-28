<?php

    class FormController extends z_controller {

        public function action_interactions(Request $req, Response $res) {
            if($req->hasFormData()) {
                // None of these fields carry validation rules, so a form
                // submission cannot produce errors here - no formErrors
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

        // Fixture for the form-ergonomics feature set: enable/disable,
        // show/hide, getValues/setValues, auto-disable-on-submit, plus the
        // _updateLayout rebuild (addCustomHTML/addSeperator survival, row
        // state, listener survival). No validation rules — submission always
        // succeeds so the auto-disable window can be observed.
        public function action_ergonomics(Request $req, Response $res) {
            if($req->hasFormData()) {
                $req->validateForm([
                    (new FormField("field_a")),
                    (new FormField("watched")),
                    (new FormField("field_half")),
                ]);

                return $res->success();
            }

            return $res->render("form/ergonomics");
        }

        // Fixture reproducing "real world" ways views bend the form API:
        // conditional wrapper visibility via closest('.form-group'),
        // cascading computed values, appending custom DOM into a field's
        // group, a hand-rolled hidden-JSON + cards multi-select, and jQuery
        // submit-button manipulation. Guards that Z.js stays compatible with
        // these patterns.
        public function action_weirdPatterns(Request $req, Response $res) {
            return $res->render("form/weirdPatterns");
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

        // Probes CanRetrieveFromInput::getFile()'s default-return branch:
        // when no file is uploaded under that key, getFile($key, $default)
        // returns $default verbatim. Used by form/file.cy.js.
        public function action_probeGetFileDefault(Request $req, Response $res) {
            $sentinel = "fake.pdf-fallback-sentinel";
            return $res->json([
                "default" => $req->getFile("nonexistent_field", $sentinel),
                "matches" => $req->getFile("nonexistent_field", $sentinel) === $sentinel,
            ]);
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

        public function action_validationMultiSelect(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_multi_select", "value_json")),
                    (new FormField("field_multi_select_required", "value"))
                        ->required()
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                $insertId = $res->insertDatabase("model_test_insert", $formResult);

                return $res->success([
                    "id" => $insertId,
                    "values_only" => $req->getPost("field_multi_select_values_only"),
                    "text_only" => $req->getPost("field_multi_select_text_only"),
                    "both" => $req->getPost("field_multi_select"),
                    "required" => $req->getPost("field_multi_select_required"),
                ]);
            }

            return $res->render("form/validationMultiSelect", [
                "exampleData" => $this->makeFood([
                    ["id" => "one",   "label" => "One"],
                    ["id" => "two",   "label" => "Two"],
                    ["id" => "three", "label" => "Three"],
                ], "id", "label"),
            ]);
        }

        // Probe for the list-aware variants of length/regex and the new
        // ->in() allow-list rule. length and regex now accept array field
        // values (multi-select); ->in() applies an in-memory allow-list
        // and works for both scalar select and array multi-select.
        // No DB persistence here, just validation echoed back as JSON.
        public function action_validationListRules(Request $req, Response $res) {
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("field_list_length"))
                        ->length(1, 2),
                    (new FormField("field_list_regex"))
                        ->regex("/^[a-z]+$/"),
                    (new FormField("field_list_in_array"))
                        ->in(["one", "two"]),
                    (new FormField("field_list_in_select"))
                        ->in(["one", "two"]),
                    // Per-item DB check on a multi-select: every picked
                    // role name must exist in z_role.
                    (new FormField("field_list_exists_multi"))
                        ->exists("z_role", "name"),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                return $res->success();
            }

            return $res->render("form/validationListRules", [
                "options" => $this->makeFood([
                    ["id" => "one",   "label" => "One"],
                    ["id" => "two",   "label" => "Two"],
                    ["id" => "three", "label" => "Three"],
                ], "id", "label"),
                "mixedCase" => $this->makeFood([
                    ["id" => "abc", "label" => "abc"],
                    ["id" => "def", "label" => "def"],
                    ["id" => "XYZ", "label" => "XYZ"],
                ], "id", "label"),
            ]);
        }

    }
?>