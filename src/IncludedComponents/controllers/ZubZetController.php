<?php

    class ZubZetController {

        public function health(Request $req, Response $res) {
            // The buffer discards connection warnings PHP 8.0 emits instead of throwing
            ob_start();
            try {
                db()->exec("SELECT 1");
                $healthy = true;
            } catch(\Throwable) {
                $healthy = false;
            }
            ob_end_clean();

            if(!$healthy) http_response_code(503);
            return $res->json(["status" => $healthy ? "healthy" : "unhealthy"]);
        }

    }

?>
