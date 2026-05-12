<?php

    // Renders framework-shipped views (IncludedComponents/views/*) directly
    // by absolute path. CanRenderView::resolvePath returns paths that already
    // start with the framework views root verbatim - bypassing the user-space
    // override lookup. That way the framework versions get exercised even
    // when the test app provides an override for the same name.
    // Covered by tests/cypress/e2e/core/framework-views.cy.js.
    class FrameworkViewProbeController extends z_controller {

        private function frameworkView(string $name): string {
            return zubzet()->z_framework_root . "IncludedComponents/views/" . $name;
        }

        public function action_login(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login.php"),
                [],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_loginSignup(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_signup.php"),
                [],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_loginForgotPassword(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_forgotpassword.php"),
                ["title" => "Forgot password"],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_loginReset(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_reset.php"),
                ["title" => "Password reset"],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        // login_verify.php branches on $opt["success"]; render once with
        // success=true (verified banner) and once with success=false (resend
        // prompt) to exercise both view branches.
        public function action_loginVerifySuccess(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_verify.php"),
                ["success" => true, "login" => "index"],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_loginVerifyFailure(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_verify.php"),
                ["success" => false, "login" => "index"],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_loginVerifyWait(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("login_verify_wait.php"),
                [],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_emailVerify(Request $req, Response $res) {
            return $res->render(
                $this->frameworkView("email_verify.php"),
                ["url" => "http://probe.example/verify-link"],
                $this->frameworkView("layout/mail_layout.php"),
            );
        }

        // Layout-only coverage: render a tiny known view inside each
        // framework layout to observe the wrapper output.
        public function action_layoutDefault(Request $req, Response $res) {
            return $res->render(
                "core/render",
                ["data" => "FrameworkLayoutProbe"],
                $this->frameworkView("layout/default_layout.php"),
            );
        }

        public function action_layoutMin(Request $req, Response $res) {
            return $res->render(
                "core/render",
                ["data" => "FrameworkLayoutProbe"],
                $this->frameworkView("layout/min_layout.php"),
            );
        }

        public function action_layoutEmpty(Request $req, Response $res) {
            return $res->render(
                "core/render",
                ["data" => "FrameworkLayoutProbe"],
                $this->frameworkView("layout/empty.php"),
            );
        }

        public function action_layoutMail(Request $req, Response $res) {
            return $res->render(
                "core/render",
                ["data" => "FrameworkLayoutProbe"],
                $this->frameworkView("layout/mail_layout.php"),
            );
        }
    }
