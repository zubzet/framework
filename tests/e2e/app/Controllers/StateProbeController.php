<?php

    use ZubZet\Framework\Message\Input\State;

    // Exercises src/Message/Input/State.php directly. State is the
    // request-context container that Router::reroute() forks via
    // State::fromOverwrite()->withPath()->withArgs(); the rest of the
    // wither API was built ahead of a planned component system (PR #98)
    // and only this controller drives it. Each action constructs an
    // empty State, applies a single scenario, and emits JSON of the
    // resulting public fields - cypress assertions in
    // tests/cypress/e2e/core/state.cy.js verify the semantics.
    class StateProbeController extends z_controller {

        private function freshState(): State {
            $state = new State();
            $state->SERVER = [];
            $state->GET = [];
            $state->POST = [];
            $state->FILES = [];
            $state->REQUEST = [];
            $state->SESSION = [];
            $state->COOKIE = [];
            $state->body = null;
            return $state;
        }

        private function snapshot(State $state): array {
            return [
                "SERVER" => $state->SERVER,
                "GET" => $state->GET,
                "POST" => $state->POST,
                "FILES" => $state->FILES,
                "REQUEST" => $state->REQUEST,
                "SESSION" => $state->SESSION,
                "COOKIE" => $state->COOKIE,
                "body" => $state->body,
            ];
        }

        // ------------------------------------------------------------------
        // fromOverwrite()
        // ------------------------------------------------------------------

        public function action_fromOverwrite_basic_merge(Request $req, Response $res) {
            $parent = $this->freshState();
            $parent->GET = ["a" => "1", "b" => "2"];

            $child = State::fromOverwrite($parent, [
                "GET" => ["b" => "overridden", "c" => "new"],
            ]);

            return $res->json([
                "parent_GET" => $parent->GET,
                "child_GET" => $child->GET,
            ]);
        }

        public function action_fromOverwrite_previous_ref(Request $req, Response $res) {
            $parent = $this->freshState();
            $parent->GET = ["a" => "original"];

            $child = State::fromOverwrite($parent, []);

            // Mutate the parent AFTER fromOverwrite. Because $child->previous
            // is a reference (note the `&` in State::fromOverwrite), the
            // child must observe the new value.
            $parent->GET["a"] = "mutated-after";

            return $res->json([
                "child_previous_GET" => $child->previous->GET,
                "child_GET" => $child->GET,
            ]);
        }

        public function action_fromOverwrite_unknown_throws(Request $req, Response $res) {
            $parent = $this->freshState();
            try {
                State::fromOverwrite($parent, ["NOT_A_FIELD" => ["k" => "v"]]);
                return $res->json(["threw" => false]);
            } catch(\InvalidArgumentException $e) {
                return $res->json([
                    "threw" => true,
                    "message" => $e->getMessage(),
                ]);
            }
        }

        public function action_fromOverwrite_parent_isolated(Request $req, Response $res) {
            // PHP arrays are value-typed, so mutating the clone's POST should
            // not bleed back into the parent. Confirms the clone is safe to
            // hand to a forked execution path.
            $parent = $this->freshState();
            $parent->POST = ["k" => "parent"];

            $child = State::fromOverwrite($parent, []);
            $child->withPost(["k" => "child"]);

            return $res->json([
                "parent_POST" => $parent->POST,
                "child_POST" => $child->POST,
            ]);
        }

        // ------------------------------------------------------------------
        // withUrl()
        // ------------------------------------------------------------------

        public function action_withUrl_all_parts(Request $req, Response $res) {
            $state = $this->freshState();
            // withUrl() calls withGet() when the URL has a query; withGet()
            // strtok()s REQUEST_URI, so a realistic state needs a baseline
            // REQUEST_URI here. See BUGS_FOUND.md for the strict-mode bug
            // when this key is absent.
            $state->SERVER["REQUEST_URI"] = "";
            $state->withUrl("https://example.com/foo/bar?x=1&y=two");

            return $res->json([
                "scheme" => $state->SERVER["REQUEST_SCHEME"] ?? null,
                "https" => $state->SERVER["HTTPS"] ?? null,
                "host" => $state->SERVER["HTTP_HOST"] ?? null,
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "redirect_url" => $state->SERVER["REDIRECT_URL"] ?? null,
                "query_string" => $state->SERVER["QUERY_STRING"] ?? null,
                "GET" => $state->GET,
            ]);
        }

        public function action_withUrl_https_on(Request $req, Response $res) {
            $state = $this->freshState();
            $state->withUrl("https://example.com/");
            return $res->json([
                "scheme" => $state->SERVER["REQUEST_SCHEME"] ?? null,
                "https" => $state->SERVER["HTTPS"] ?? null,
            ]);
        }

        public function action_withUrl_https_off(Request $req, Response $res) {
            $state = $this->freshState();
            $state->withUrl("http://example.com/");
            return $res->json([
                "scheme" => $state->SERVER["REQUEST_SCHEME"] ?? null,
                "https" => $state->SERVER["HTTPS"] ?? null,
            ]);
        }

        public function action_withUrl_path_only(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_SCHEME"] = "preserved";
            $state->SERVER["HTTP_HOST"] = "preserved.example";
            $state->withUrl("/just/a/path");

            return $res->json([
                "scheme" => $state->SERVER["REQUEST_SCHEME"] ?? null,
                "host" => $state->SERVER["HTTP_HOST"] ?? null,
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
            ]);
        }

        public function action_withUrl_query_only(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_URI"] = "/original";
            $state->withUrl("?fresh=value");

            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "query_string" => $state->SERVER["QUERY_STRING"] ?? null,
                "GET" => $state->GET,
            ]);
        }

        // ------------------------------------------------------------------
        // withPath()
        // ------------------------------------------------------------------

        public function action_withPath_basic(Request $req, Response $res) {
            $state = $this->freshState();
            $state->withPath("/foo/bar");
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "redirect_url" => $state->SERVER["REDIRECT_URL"] ?? null,
            ]);
        }

        public function action_withPath_strips_leading_slashes(Request $req, Response $res) {
            $state = $this->freshState();
            $state->withPath("///foo");
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "redirect_url" => $state->SERVER["REDIRECT_URL"] ?? null,
            ]);
        }

        public function action_withPath_preserves_query(Request $req, Response $res) {
            // Documented carry-over: when only the path is changed, any
            // prior QUERY_STRING is preserved on the new REQUEST_URI.
            // (Confirmed intended in the original review of State.php.)
            $state = $this->freshState();
            $state->SERVER["QUERY_STRING"] = "x=1&y=two";
            $state->withPath("/new-path");
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "redirect_url" => $state->SERVER["REDIRECT_URL"] ?? null,
            ]);
        }

        public function action_withPath_no_query(Request $req, Response $res) {
            $state = $this->freshState();
            // No QUERY_STRING set at all.
            $state->withPath("/new-path");
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
            ]);
        }

        // ------------------------------------------------------------------
        // withGet()
        // ------------------------------------------------------------------

        public function action_withGet_replaces(Request $req, Response $res) {
            $state = $this->freshState();
            $state->GET = ["old" => "1"];
            $state->SERVER["REQUEST_URI"] = "/foo?old=1";
            $state->withGet(["only" => "new"]);

            return $res->json([
                "GET" => $state->GET,
                "query_string" => $state->SERVER["QUERY_STRING"] ?? null,
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "REQUEST" => $state->REQUEST,
            ]);
        }

        public function action_withGet_empty_clears_query(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_URI"] = "/foo?x=1";
            $state->withGet([]);
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                "query_string" => $state->SERVER["QUERY_STRING"] ?? null,
                "GET" => $state->GET,
            ]);
        }

        // Regression: withGet() must survive a State that has never set
        // SERVER["REQUEST_URI"] (CLI bootstraps, ad-hoc construction).
        // Earlier the call indexed the key directly and died under PHP 8
        // strict mode; now guarded with `?? ""`.
        public function action_withGet_missing_request_uri(Request $req, Response $res) {
            $state = $this->freshState();
            // REQUEST_URI intentionally absent.
            try {
                $state->withGet(["fresh" => "value"]);
                return $res->json([
                    "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
                    "query_string" => $state->SERVER["QUERY_STRING"] ?? null,
                    "GET" => $state->GET,
                    "threw" => false,
                ]);
            } catch(\Throwable $e) {
                return $res->json([
                    "threw" => true,
                    "type" => get_class($e),
                    "message" => $e->getMessage(),
                ]);
            }
        }

        public function action_withGet_preserves_base_path(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_URI"] = "/some/path?stale=yes";
            $state->withGet(["fresh" => "yes"]);
            return $res->json([
                "request_uri" => $state->SERVER["REQUEST_URI"] ?? null,
            ]);
        }

        // ------------------------------------------------------------------
        // withPost(), withFiles(), withSession(), withBody(), withMethod(),
        // withCookies(), withReferer()
        // ------------------------------------------------------------------

        public function action_withPost_replaces_and_updates_request(Request $req, Response $res) {
            $state = $this->freshState();
            $state->POST = ["old" => "x"];
            $state->REQUEST = ["old" => "x"];
            $state->withPost(["fresh" => "value"]);
            return $res->json([
                "POST" => $state->POST,
                "REQUEST" => $state->REQUEST,
            ]);
        }

        public function action_withFiles_replaces(Request $req, Response $res) {
            $state = $this->freshState();
            $state->FILES = ["before" => ["name" => "before.txt"]];
            $state->withFiles(["after" => ["name" => "after.txt"]]);
            return $res->json([
                "FILES" => $state->FILES,
            ]);
        }

        public function action_withSession_replaces(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SESSION = ["before" => 1];
            $state->withSession(["after" => 2]);
            return $res->json([
                "SESSION" => $state->SESSION,
            ]);
        }

        public function action_withBody_sets(Request $req, Response $res) {
            $state = $this->freshState();
            $state->body = "old body";
            $state->withBody("new body");
            return $res->json([
                "body" => $state->body,
            ]);
        }

        public function action_withMethod_sets(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_METHOD"] = "GET";
            $state->withMethod("DELETE");
            return $res->json([
                "method" => $state->SERVER["REQUEST_METHOD"] ?? null,
            ]);
        }

        public function action_withCookies_replaces_and_updates_request(Request $req, Response $res) {
            $state = $this->freshState();
            $state->COOKIE = ["before" => "1"];
            $state->REQUEST = ["before" => "1"];
            $state->withCookies(["after" => "2"]);
            return $res->json([
                "COOKIE" => $state->COOKIE,
                "REQUEST" => $state->REQUEST,
            ]);
        }

        public function action_withReferer_sets(Request $req, Response $res) {
            $state = $this->freshState();
            $state->withReferer("https://prev.example/page");
            return $res->json([
                "referer" => $state->SERVER["HTTP_REFERER"] ?? null,
            ]);
        }

        // ------------------------------------------------------------------
        // updateRequest() (private; observed via the three withers that
        // call it). Merge order is array_merge(GET, POST, COOKIE), so on
        // key collision: COOKIE > POST > GET.
        // ------------------------------------------------------------------

        public function action_updateRequest_precedence(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["REQUEST_URI"] = "";
            $state->withGet(["common" => "fromGet", "g" => "g"])
                  ->withPost(["common" => "fromPost", "p" => "p"])
                  ->withCookies(["common" => "fromCookie", "c" => "c"]);
            return $res->json([
                "REQUEST" => $state->REQUEST,
            ]);
        }

        // ------------------------------------------------------------------
        // withArgs() - keep argv[0] (script) and argv[1] (command),
        // append the new args after.
        // ------------------------------------------------------------------

        public function action_withArgs_keeps_first_two(Request $req, Response $res) {
            $state = $this->freshState();
            $state->SERVER["argv"] = ["index.php", "command", "stale1", "stale2"];
            $state->withArgs(["fresh1", "fresh2", "fresh3"]);
            return $res->json([
                "argv" => $state->SERVER["argv"] ?? null,
            ]);
        }

        public function action_withArgs_empty_prior_argv(Request $req, Response $res) {
            $state = $this->freshState();
            // No SERVER["argv"] set at all.
            $state->withArgs(["a", "b"]);
            return $res->json([
                "argv" => $state->SERVER["argv"] ?? null,
            ]);
        }

        // ------------------------------------------------------------------
        // withPreviousAsReferer()
        // ------------------------------------------------------------------

        private function withChildState(array $previousServer): State {
            $parent = $this->freshState();
            foreach($previousServer as $k => $v) {
                $parent->SERVER[$k] = $v;
            }
            return State::fromOverwrite($parent, []);
        }

        public function action_withPreviousAsReferer_happy(Request $req, Response $res) {
            $child = $this->withChildState([
                "REQUEST_SCHEME" => "https",
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page?x=1",
            ]);
            $child->withPreviousAsReferer();
            return $res->json([
                "referer" => $child->SERVER["HTTP_REFERER"] ?? null,
            ]);
        }

        public function action_withPreviousAsReferer_no_previous_throws(Request $req, Response $res) {
            $state = $this->freshState();
            try {
                $state->withPreviousAsReferer();
                return $res->json(["threw" => false]);
            } catch(\LogicException $e) {
                return $res->json([
                    "threw" => true,
                    "message" => $e->getMessage(),
                ]);
            }
        }

        public function action_withPreviousAsReferer_no_host_throws(Request $req, Response $res) {
            $child = $this->withChildState([
                "REQUEST_SCHEME" => "https",
                // HTTP_HOST intentionally absent.
                "REQUEST_URI" => "/old/page",
            ]);
            try {
                $child->withPreviousAsReferer();
                return $res->json(["threw" => false]);
            } catch(\LogicException $e) {
                return $res->json([
                    "threw" => true,
                    "message" => $e->getMessage(),
                ]);
            }
        }

        public function action_withPreviousAsReferer_no_scheme_throws(Request $req, Response $res) {
            $child = $this->withChildState([
                // REQUEST_SCHEME intentionally absent.
                "HTTP_HOST" => "prev.example",
                "REQUEST_URI" => "/old/page",
            ]);
            try {
                $child->withPreviousAsReferer();
                return $res->json(["threw" => false]);
            } catch(\LogicException $e) {
                return $res->json([
                    "threw" => true,
                    "message" => $e->getMessage(),
                ]);
            }
        }

        // ------------------------------------------------------------------
        // fromRequest() - <#decURI#> POST prefix decoding.
        // ------------------------------------------------------------------

        // This action is called as a POST and proves fromRequest()
        // re-runs its decURI walker on $_POST. Exercised by cypress
        // posting `<#decURI#>foo%20bar%26baz`.
        public function action_decURI(Request $req, Response $res) {
            $state = State::fromRequest();
            return $res->json([
                "decoded" => $state->POST["raw"] ?? null,
            ]);
        }

    }

?>
