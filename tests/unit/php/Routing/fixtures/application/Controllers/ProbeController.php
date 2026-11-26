<?php

    /**
     * The controller of the application that tests/unit/php/Routing/RequestLifecycleTest.php
     * sends its requests to. Every action prints a marker where the code must
     * or must not continue.
     */
    class ProbeController {

        // Found by name

        public function action_index(Request $req, Response $res) {
            echo "index";
        }

        public function action_action(Request $req, Response $res) {
            echo "action";
        }

        public function action_fallback(Request $req, Response $res) {
            echo "fallback";
        }

        public function action_parameters(Request $req, Response $res) {
            echo implode(",", $req->getParameters());
        }

        public function action_first(Request $req, Response $res) {
            echo $req->getParameters(0, 1, "expected") ? "yes" : "no";
        }

        public function action_route(Request $req, Response $res, string ...$arguments) {
            echo "route:" . json_encode($req->getRouteParameter()) . ":" . implode(",", $arguments);
        }

        // Ending the request

        public function action_rest(Request $req, Response $res) {
            $res->success(["name" => $req->getPost("name", "nobody")]);
            echo "after-success";
        }

        public function action_render(Request $req, Response $res) {
            $res->render("probe/page", ["name" => "Ada"], "probe/layout");
            echo "after-render";
        }

        public function action_redirect(Request $req, Response $res) {
            $res->rerouteUrl("probe/action");
            echo "after-redirect";
        }

        // The three spellings of stop()
        public function action_stop(Request $req, Response $res) {
            echo "printed";

            $spelling = $req->getParameters(0, 1);
            if("request" === $spelling) $req->stop();
            if("response" === $spelling) $res->stop();
            if("helper" === $spelling) stop();

            echo "|after-stop";
        }

        public function action_catch(Request $req, Response $res) {
            try {
                $res->success();
            } catch(\Exception $e) {
                echo "caught";
            }
            echo "after-catch";
        }

        public function action_finally(Request $req, Response $res) {
            try {
                $res->success();
            } finally {
                echo "finally-ran";
            }
        }

        public function action_throws(Request $req, Response $res) {
            throw new \RuntimeException("broken action");
        }

        // Guards

        public function action_guard(Request $req, Response $res) {
            $req->checkPermission("probe.view");
            echo "behind-guard";
        }

        // Both shapes of the check back to back
        public function action_ask(Request $req, Response $res) {
            echo $req->checkPermission("probe.view", true) ? "allowed" : "denied";
            echo "|";
            $req->checkPermission("probe.view");
            echo "passed";
        }

        public function action_views(Request $req, Response $res) {
            if($req->checkPermission("probe.all", true)) {
                return $res->success(["view" => "all"]);
            }

            return $res->success(["view" => "own"]);
        }

        public function action_console(Request $req, Response $res) {
            $req->checkPermission("console");
            echo "behind-console";
        }

        // Reroutes

        public function action_reroute(Request $req, Response $res) {
            $res->reroute(["probe", "action"]);
            echo "|after-reroute";
        }

        public function action_reroutefinal(Request $req, Response $res) {
            $res->reroute(["probe", "action"], false, true);
            echo "|after-reroute";
        }

        // The rerouted action answers, which ends the request of this one too
        public function action_rerouteanswered(Request $req, Response $res) {
            $res->reroute(["probe", "rest"]);
            echo "|after-reroute";
        }

        public function action_alias(Request $req, Response $res) {
            $res->reroute([1 => "parameters"], true);
        }

        public function action_loop(Request $req, Response $res) {
            $res->reroute(["probe", "loop"]);
        }

        public function action_store(Request $req, Response $res) {
            $req->store["from"] = "store";
            $res->reroute(["probe", "stored"]);
        }

        public function action_stored(Request $req, Response $res) {
            echo $req->store["from"] ?? "empty";
        }

        // Middlewares

        public function passing(Request $req, Response $res) {
            echo "passing|";
            return true;
        }

        public function blocking(Request $req, Response $res) {
            echo "blocked";
        }

        public function refusing(Request $req, Response $res) {
            $res->error("refused");
        }

        public function afterware(Request $req, Response $res) {
            echo "|afterware";
        }

    }

?>
