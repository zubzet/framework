<?php

    class AssetProxyController extends z_controller {

        public function action_sibling_prefix_traversal(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/");
            zubzet()->assetProxy->serve("../webroot_security.txt");
        }

        public function action_directory_request(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/");
            zubzet()->assetProxy->serve("assets");
        }

        public function action_null_mime(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/assets/");
            zubzet()->assetProxy->serve("unknown_mime");
        }

        public function action_symlink_escape(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/assets/");
            zubzet()->assetProxy->serve("outside_symlink");
        }

        public function action_null_byte(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/assets/");
            zubzet()->assetProxy->serve("assets.txt\0.png");
        }

        public function action_source_root_empty(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/");
            zubzet()->assetProxy->serve("");
        }

        public function action_source_root_dot(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/");
            zubzet()->assetProxy->serve(".");
        }

        public function action_source_root_dot_slash(Request $req, Response $res) {
            zubzet()->assetProxy->registerWebRootSource("webroot/");
            zubzet()->assetProxy->serve("./");
        }
    }
