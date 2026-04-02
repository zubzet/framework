<?php

    class SettingsController extends z_controller  {

        public function action_runtimeSet(Request $req, Response $res) {
            $key = $req->get('key');
            $value = $req->get('value');

            zubzet()->config[$key] = $value;
        }
    }

?>