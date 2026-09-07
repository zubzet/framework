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

        public function action_fallback_locale(Request $req, Response $res) {
            echo __("language.message", locale: "fr");
        }

        public function action_catalogue_override(Request $req, Response $res) {
            echo implode("\n", [
                __("form.unsaved"),
                __("form.saved"),
                __("form.error_required"),
                __("language.message"),
            ]);
        }

        public function action_lang(Request $req, Response $res) {
            return $res->render("translation/lang");
        }

        public function action_loader(Request $req, Response $res) {
            $format = $req->getUrlParts()[2];
            echo __("language.message", domain: "{$format}_loader");
        }

    }

?>
