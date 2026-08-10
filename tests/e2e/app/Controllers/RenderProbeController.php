<?php

    /**
     * Drives the Katana render engine's resolution + $opt contract through the
     * real render path (Rendering\CanRenderView -> Rendering\Katana\Engine).
     * Covered by:
     *   core/view-resolution.cy.js - extension/dot equivalence, override, cross-root
     *   core/opt-contract.cy.js    - $opt shape, title, generateResourceLink
     *   core/view-fallback.cy.js   - missing view/layout -> 500 page
     *
     * Most actions render into the framework's chrome-free layout/empty so the
     * assertions see only the view's own output.
     */
    class RenderProbeController extends z_controller {

        // -- $opt contract ------------------------------------------------------

        public function action_opt(Request $req, Response $res) {
            return $res->render("renderprobe/opt", [
                "a" => "alpha",
                "title" => "Custom Title Marker",
            ], "layout/empty");
        }

        // No caller title -> $opt["title"] falls back to config("pageName").
        public function action_optDefaultTitle(Request $req, Response $res) {
            return $res->render("renderprobe/opt", [
                "a" => "alpha",
            ], "layout/empty");
        }

        public function action_resourceLink(Request $req, Response $res) {
            return $res->render("renderprobe/resource", [], "layout/empty");
        }

        // -- View-name resolution ----------------------------------------------

        // The same app view addressed four ways: bare, ".blade.php", ".php" and
        // dotted. All must resolve to app/Views/core/render.blade.php.
        public function action_extNone(Request $req, Response $res) {
            return $res->render("core/render", ["data" => "ExtNone"], "layout/empty");
        }

        public function action_extBlade(Request $req, Response $res) {
            return $res->render("core/render.blade.php", ["data" => "ExtBlade"], "layout/empty");
        }

        public function action_extPhp(Request $req, Response $res) {
            return $res->render("core/render.php", ["data" => "ExtPhp"], "layout/empty");
        }

        public function action_dotted(Request $req, Response $res) {
            return $res->render("core.render", ["data" => "Dotted"], "layout/empty");
        }

        // `login` exists in BOTH roots (app override + framework copy). Without the
        // framework-priority escape hatch, the userspace copy must win.
        public function action_overrideAppWins(Request $req, Response $res) {
            return $res->render("login", [], "layout/empty");
        }

        // Cross-root composition: an app view (app/Views/core/render) rendered into
        // a framework layout (layout/min_layout), which in turn pulls in a framework
        // component (<x-zubzet::head/>). All three resolve through the one finder
        // chain - the old single-root renderer could not span roots like this.
        public function action_crossRoot(Request $req, Response $res) {
            return $res->render("core/render", ["data" => "CrossRootBody"], "layout/min_layout");
        }

        // -- Not-found fallback -------------------------------------------------

        // A missing view is caught in CanRenderView and re-rendered as the
        // framework 500 page in the guaranteed framework layout/min_layout.
        public function action_missingView(Request $req, Response $res) {
            return $res->render("renderprobe/this-view-does-not-exist-xyz", ["data" => "x"]);
        }

        // A present view whose layout is missing: the @extends($layout) fails, and
        // because the 500 fallback ignores the caller's layout, it still renders.
        public function action_validViewMissingLayout(Request $req, Response $res) {
            return $res->render("core/render", ["data" => "x"], "layout/this-layout-does-not-exist-xyz");
        }

        // The double fault: BOTH the view and its layout are missing. The
        // guaranteed-layout fallback still renders the 500 page.
        public function action_missingViewAndLayout(Request $req, Response $res) {
            return $res->render(
                "renderprobe/this-view-does-not-exist-xyz",
                ["data" => "x"],
                "layout/this-layout-does-not-exist-xyz",
            );
        }

    }

?>
