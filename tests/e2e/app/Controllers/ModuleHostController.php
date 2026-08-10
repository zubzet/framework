<?php

    use Module\Guestbook\Support\EntryFormatter;

    /**
     * Probes the app-side view of the module system: composer autoloading of a
     * module namespace, module-contributed config defaults, and a module view
     * shadowing a framework view.
     */
    class ModuleHostController extends z_controller {

        public function action_service(Request $req, Response $res) {
            echo '<span data-test="app-uses-module-service">' . EntryFormatter::format("QA", "module namespace works") . '</span>';
        }

        public function action_config(Request $req, Response $res) {
            echo '<span data-test="guestbook-config">' . e(config("guestbook_title", default: "fallback-title")) . '</span>';
        }

        public function action_email(Request $req, Response $res) {
            return $res->render("email_too_many_logins", [
                "date" => "2026-07-28 12:00:00",
                "ip" => "203.0.113.7",
            ], "layout/empty");
        }

    }

?>
