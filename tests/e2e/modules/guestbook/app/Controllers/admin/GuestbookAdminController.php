<?php

    class GuestbookAdminController extends z_controller {

        public function action_stats(Request $req, Response $res) {
            // Bare name "GuestbookStats" resolves the model one directory deep
            // inside the module root (recursive lookup).
            return $res->render("guestbook/admin", [
                "count" => $req->getModel("GuestbookStats")->countEntries(),
            ]);
        }

        public function action_statsExplicit(Request $req, Response $res) {
            // Dot notation addresses the exact sub-path per root; it must land
            // on the same file as the bare-name lookup above.
            return $res->render("guestbook/admin", [
                "count" => $req->getModel("reports.GuestbookStats")->countEntries(),
            ]);
        }

        public function action_statsMissing(Request $req, Response $res) {
            // A dotted name pointing at a directory that does not hold the
            // model must throw; the recursive index never rescues explicit
            // paths. The e2e spec asserts this request fails.
            $req->getModel("nowhere.GuestbookStats");
        }

    }

?>
