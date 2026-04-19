<?php
/**
 * This file holds essential parts for the framework that will be pasted into the layout.
 */

/**
 * Call this to paste the essential head part of a page into the layout
 * @param array $opt Object holding options for rendering
 */
function essentialsHead($opt, $customBootstrap = false) {
    $opt["assets"]->js("jquery/jquery.min.js");
    if (!$customBootstrap) {
        $opt["assets"]->js("bootstrap/js/bootstrap.bundle.min.js");
        $opt["assets"]->css("bootstrap/css/bootstrap.min.css");
    }
    $opt["assets"]->js("js/bs-custom-file-input.js");
    $opt["assets"]->js("js/Z.js");

    $opt["assets"]->css("fontawesome/css/all.min.css");
    $opt["assets"]->css("fontawesome/css/v4-shims.min.css");
    ?>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

    <?= $opt["assets"]->renderCss(); ?>
    <?= $opt["assets"]->renderJs(); ?>

    <script>
        Z.Request.rootPath = "<?= $opt["root"]; ?>";
        Z.Request.rootHost = "<?= $opt["request"]->getRoot(); ?>";
        Z.Request.absRoot = "<?= $opt["absRoot"]; ?>";
    </script>

<?php }

/**
 * Call this to paste the essential body part of a page into the layout
 * @param array $opt Object holding options for rendering
 */
function essentialsBody($opt) { ?>
    <?= $opt["assets"]->renderModules(); ?>

    <!-- TOKEN EXPIRED -->
    <?php if($opt["user"]->isLoggedIn) { ?>
        <script>
            var token_expired_callback = setInterval(function() {
                if(document.cookie.indexOf("z_login_token") < 0) {
                    location.reload();
                }
            }, 1000);
        </script>
        <!-- TOKEN EXPIRED -->
    <?php } ?>
<?php } ?>
