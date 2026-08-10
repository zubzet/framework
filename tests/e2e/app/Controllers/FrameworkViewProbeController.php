<?php

    // Renders framework-shipped views (IncludedComponents/views/*) even when the test app
    // provides an override for the same name, so the framework versions get exercised.
    // frameworkView() sets Engine::$prioritizeFrameworkViews before the render, which flips the
    // finder order to framework-first, so the framework copy wins.
    // Covered by tests/cypress/e2e/core/framework-views.cy.js.

    use ZubZet\Framework\Rendering\Katana\Engine;

    class FrameworkViewProbeController extends z_controller {

        // Force the framework copy for the upcoming render, then reference the view by name.
        private function frameworkView(string $name): string {
            Engine::$prioritizeFrameworkViews = true;
            return $name;
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
