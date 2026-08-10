<?php

    // Observability probes for the Galera cluster the e2e stack runs on.
    // action_status exposes the wsrep state the framework connection sees;
    // action_slowread spans two queries across a sleep so a node can be
    // killed mid-request (tests/cypress/e2e/database/failover.cy.js);
    // action_write inserts into test_cluster so replication can be
    // asserted on the other nodes (tests/cypress/e2e/database/cluster.cy.js).
    class DatabaseClusterProbeController extends z_controller {

        public function action_status(Request $req, Response $res) {
            $rows = db()->exec("SHOW STATUS LIKE 'wsrep_%'")->resultToArray();
            $status = array_column($rows, "Value", "Variable_name");

            return $res->json([
                "clusterSize" => (int) ($status["wsrep_cluster_size"] ?? 0),
                "ready" => $status["wsrep_ready"] ?? "OFF",
                "state" => $status["wsrep_local_state_comment"] ?? "unknown",
            ]);
        }

        // First query pins a connection and publishes which node it landed on
        // (round-robin, so the spec cannot know beforehand); the sleep gives
        // the spec time to kill exactly that node, the second query then hits
        // a dead connection and must be recovered by the reconnect-retry in
        // Connection::exec().
        public function action_slowread(Request $req, Response $res) {
            $node = $this->currentNode();
            file_put_contents("galera-target-node.txt", $node);

            sleep(3);

            $count = db()->exec("SELECT COUNT(*) AS c FROM test_cluster")->resultToArray();
            $recoveredOn = $this->currentNode();

            return $res->json([
                "survived" => true,
                "rows" => (int) $count[0]["c"],
                "diedOn" => $node,
                "recoveredOn" => $recoveredOn,
            ]);
        }

        public function action_write(Request $req, Response $res) {
            $marker = uniqid("e2e_", true);
            db()->exec("INSERT INTO test_cluster (marker) VALUES (?)", "s", $marker);

            $count = db()->exec("SELECT COUNT(*) AS c FROM test_cluster")->resultToArray();

            return $res->json([
                "marker" => $marker,
                "rows" => (int) $count[0]["c"],
            ]);
        }

        private function currentNode(): string {
            return db()->exec("SELECT @@hostname AS h")->resultToArray()[0]["h"];
        }

    }

?>
