<?php
    class Update_v0_10_0 extends Update {
        public function run(Request $req, Response $res) {
            echo "Cleaning up no longer used files...\n";
            if(file_exists("composer.phar")) unlink("composer.phar");
        }
    }
?>