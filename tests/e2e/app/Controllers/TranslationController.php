<?php

    class TranslationController extends z_controller {

        public function action_domain_message(Request $req, Response $res) {
            echo __("language.message", domain: "test");
        }

        public function action_message(Request $req, Response $res) {
            echo __("language.message");
        }
    }

?>
