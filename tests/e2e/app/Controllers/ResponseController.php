<?php

    class ResponseController extends z_controller {

        public function action_json_happy(Request $req, Response $res) {
            $res->json(["ok" => true]);
        }

        public function action_json_non_encodable(Request $req, Response $res) {
            $res->json(fopen("php://memory", "r"));
        }

        // -------------------------------------------------------------
        // Response::getCookieDomainScope() probes. Reads the
        // login_scope_allow_subdomains config; the docker test env doesn't
        // pin it, so a runtime DynamicAttributes override works here
        // (same idiom as action_currentUrl's hostOverride).
        // -------------------------------------------------------------

        public function action_cookieDomainScopeDefault(Request $req, Response $res) {
            return $res->json([
                "scope" => $res->getCookieDomainScope(),
            ]);
        }

        public function action_cookieDomainScopeSubdomain(Request $req, Response $res) {
            zubzet()->login_scope_allow_subdomains = "true";
            return $res->json([
                "scope" => $res->getCookieDomainScope(),
                "domain" => $req->getDomain(),
            ]);
        }

        // Logs in user 1 with login_scope_allow_subdomains=true so the
        // primary z_login_token cookie is emitted with the subdomain-wide
        // Domain attribute. Cypress inspects Set-Cookie to verify the
        // browser would share it across subdomains.
        public function action_loginAsWithSubdomainScope(Request $req, Response $res) {
            zubzet()->login_scope_allow_subdomains = "true";
            $res->loginAs(1);
        }

        // Logs in user 1 with login_scope_allow_subdomains=false so the
        // emitted cookie is NOT shared across subdomains.
        public function action_loginAsWithoutSubdomainScope(Request $req, Response $res) {
            zubzet()->login_scope_allow_subdomains = "false";
            $res->loginAs(1);
        }

        // Drives Response::deleteOldLoginCookieDomainScope() (private,
        // called by loginAs/logout when login_scope_allow_subdomains_delete_domainscope_name
        // is set). Forces a Set-Cookie clearing the legacy-domain cookie.
        public function action_loginAsWithLegacyScope(Request $req, Response $res) {
            zubzet()->login_scope_allow_subdomains_delete_domainscope_name = ".legacy.example";
            $res->loginAs(1);
        }

        // -------------------------------------------------------------
        // View-driven probes for insertDatabase / updateDatabase /
        // insertOrUpdateDatabase. These mirror the documented production
        // pattern: GET renders a Z.Forms view, POST goes through
        // validateForm -> insertDatabase. (Cypress's cy.request POSTs
        // straight to the action, just like the Z.Forms front-end would.)
        //
        // The form has an optional file field; submitting without a file
        // exercises uploadFromForm's noSave branch and the matching skip
        // in insertDatabase/updateDatabase.
        // -------------------------------------------------------------

        private function probeFields(): array {
            return [
                (new FormField("col_a"))->required()->length(1, 64),
                (new FormField("col_b"))->required()->integer(),
                (new FormField("file_id"))->file(262144, ["pdf"]),
            ];
        }

        public function action_insertForm(Request $req, Response $res) {
            if ($req->hasFormData()) {
                $formResult = $req->validateForm($this->probeFields());
                if ($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
                $id = $res->insertDatabase("z_probe_form", $formResult, ["created_by" => 7]);
                return $res->success(["id" => $id]);
            }
            return $res->render("response_probe/form", ["row" => null]);
        }

        public function action_updateForm(Request $req, Response $res) {
            $id = (int) $req->getParameters(0, 1);
            $row = db()->exec("SELECT * FROM z_probe_form WHERE id = ?", "i", $id)
                       ->resultToLine();

            if ($req->hasFormData()) {
                $formResult = $req->validateForm($this->probeFields());
                if ($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
                $res->updateDatabase("z_probe_form", "id", "i", $id, $formResult);
                return $res->success(["id" => $id]);
            }
            return $res->render("response_probe/form", ["row" => $row]);
        }

        public function action_insertOrUpdateForm(Request $req, Response $res) {
            $idParam = $req->getParameters(0, 1);
            $id = $idParam === null ? null : (int) $idParam;
            $row = $id ? db()->exec("SELECT * FROM z_probe_form WHERE id = ?", "i", $id)
                              ->resultToLine() : null;

            if ($req->hasFormData()) {
                $formResult = $req->validateForm($this->probeFields());
                if ($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
                $resultId = $res->insertOrUpdateDatabase(
                    "z_probe_form", "id", "i", $id,
                    $formResult, ["created_by" => 7],
                );
                return $res->success(["id" => $resultId]);
            }
            return $res->render("response_probe/form", ["row" => $row]);
        }

        // Convenience read-back probe. Used by cypress to assert the row
        // state after a form submission instead of opening a separate DB
        // channel.
        public function action_probeRow(Request $req, Response $res) {
            $id = (int) $req->getParameters(0, 1);
            $row = db()->exec("SELECT * FROM z_probe_form WHERE id = ?", "i", $id)
                       ->resultToLine();
            return $res->json($row);
        }

        // -------------------------------------------------------------
        // Response::reroute() probes. Non-alias branch routes through
        // zubzet()->reroute($path) directly; $final=true ends the
        // request via exit (line 53).
        // -------------------------------------------------------------

        public function action_rerouteNonAlias(Request $req, Response $res) {
            $res->reroute(["Core", "action"]);
        }

        public function action_rerouteFinal(Request $req, Response $res) {
            $res->reroute(["Core", "action"], false, true);
            // @codeCoverageIgnoreStart
            // Unreachable - reroute($final=true) calls exit on Response.php:53.
            // The cypress assertion proves this echo never runs.
            echo "AFTER_FINAL_MARKER";
            // @codeCoverageIgnoreEnd
        }

        // -------------------------------------------------------------
        // Full CED test surface. action_cedForm renders the Z.Forms-based
        // view and accepts POST submissions, going through the documented
        // validateCED -> doCED pipeline against z_probe_ced.
        //
        // The four Z values (create / edit / delete / unknown) plus the
        // hasErrors short-circuit are all exercised from this one action.
        // -------------------------------------------------------------

        public function action_cedForm(Request $req, Response $res) {
            if ($req->hasFormData()) {
                $formResult = $req->validateCED("items", [
                    (new FormField("name"))->required()->length(1, 64),
                    (new FormField("note"))->required()->length(1, 255),
                ]);
                if ($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
                $res->doCED("z_probe_ced", $formResult);
                return $res->success();
            }

            $rows = db()->exec("SELECT * FROM z_probe_ced WHERE active = 1")
                        ->resultToArray();
            $items = $this->makeCEDFood($rows, ["name", "note"]);
            return $res->render("response_probe/ced", ["items" => $items]);
        }

        // Reads the CED table back so cypress can confirm row state after
        // a submission without needing an external DB channel.
        public function action_probeCedRows(Request $req, Response $res) {
            $rows = db()->exec("SELECT id, name, note, active FROM z_probe_ced ORDER BY id")
                        ->resultToArray();
            return $res->json($rows);
        }

    }
