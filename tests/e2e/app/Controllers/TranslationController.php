<?php

    class TranslationController extends z_controller {


        public function action_default_domain(Request $req, Response $res) {
            echo __("language.default");
        }

        public function action_domain(Request $req, Response $res) {
            echo __("language.domain", domain: "test");
        }

        public function action_preferred(Request $req, Response $res) {
            echo __("language.preferred");
        }

    }

?>
