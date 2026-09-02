<?php

    class TranslationController extends z_controller {

        public function action_domain_message(Request $req, Response $res) {
            echo __("language.message", domain: "test");
        }

        public function action_message(Request $req, Response $res) {
            echo __("language.message");
        }

        public function action_param_message(Request $req, Response $res) {
            echo __("language.param_message", [
                "{first_param}" => "First",
                "{second_param}" => "Second"
            ]);
        }

        public function action_defined_locale(Request $req, Response $res) {
            echo __("language.message", locale: "de");
        }
    }

?>
