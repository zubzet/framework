<?php

    class DashboardController extends z_controller {

        public function action_index(\Request $req, \Response $res) {
            echo '<span data-test="dashboard-controller">Dashboard Controller</span>';
        }

        public function action_test(\Request $req, \Response $res) {
            $res->render("dashboard/test.blade.php", [
                "heading" => "BladeOne is live",
                "subtitle" => "Rendered through the new Renderer pipeline",
                "user_name" => "<b>Alex</b>",
                "items" => [
                    ["name" => "Renderer interface", "ready" => true],
                    ["name" => "BladeOneRenderer", "ready" => true],
                    ["name" => "PSR-16 file cache", "ready" => true],
                    ["name" => "Closure-array fallback", "ready" => true],
                ],
                "status" => "ok",
                "active" => true,
            ]);
        }

        public function action_test2(\Request $req, \Response $res) {
            $res->render("dashboard/test2.blade.php", []);
        }

    }

?>