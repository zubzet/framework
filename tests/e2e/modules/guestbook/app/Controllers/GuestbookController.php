<?php

    class GuestbookController extends z_controller {

        public function action_index(Request $req, Response $res) {
            return $res->render("guestbook/index", [
                "entries" => $req->getModel("Guestbook")->getEntries(),
                "title" => config("guestbook_title", default: "Guestbook"),
            ]);
        }

        public function action_add(Request $req, Response $res) {
            $author = $req->getPost("author");
            $message = $req->getPost("message");

            $hasInput = is_string($author) && "" !== $author
                && is_string($message) && "" !== $message;

            if($hasInput) {
                // Clamp to the column widths so oversize input cannot error.
                $author = mb_substr($author, 0, 64);
                $message = mb_substr($message, 0, 255);
                $req->getModel("Guestbook")->addEntry($author, $message);
            }

            return $res->render("guestbook/index", [
                "entries" => $req->getModel("Guestbook")->getEntries(),
                "title" => config("guestbook_title", default: "Guestbook"),
            ]);
        }

    }

?>
