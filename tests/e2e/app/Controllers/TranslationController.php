<?php

    class TranslationController extends z_controller {


        public function action_default_domain(Request $req, Response $res) {
            echo __("language.default");
        }

    }

?>
