<?php

    class ResponseController extends z_controller {

        public function action_json_happy(Request $req, Response $res) {
            $res->json(["ok" => true]);
        }

        public function action_json_non_encodable(Request $req, Response $res) {
            $res->json(fopen("php://memory", "r"));
        }
    }
