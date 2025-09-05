<?php 
/**
 * This file holds essential parts fot the framework that will be pasted into the layout.
 */

/**
 * Call this to paste the essential head part of a page into the layout
 * @param object $opt Object holding options for rendering
 */
function essentialsHead($opt, $customBootstrap) { ?>

    <script src="<?php $opt["generateResourceLink"]("assets/js/jquery.min.js"); ?>"></script>
    <script src="<?php $opt["generateResourceLink"]("assets/js/popper.min.js"); ?>"></script>
    
    <?php if(!$customBootstrap) { ?>
        <script src="<?php $opt["generateResourceLink"]("assets/js/bootstrap.min.js"); ?>"></script>
    <?php } ?>
    
    <script src="<?php $opt["generateResourceLink"]("assets/js/bs-custom-file-input.js"); ?>"></script>
    <script src="<?php $opt["generateResourceLink"]("assets/js/Z.js") ?>"></script>

    <link href="<?php $opt["generateResourceLink"]("assets/css/bootstrap.min.css"); ?>" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/font-awesome/all.min.css") ?>">
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/font-awesome/brands.min.css") ?>">
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/font-awesome/v4-shims.min.css") ?>">
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("assets/css/font-awesome/fontawesome.min.css") ?>">

    <script>
        Z.Request.rootPath = "<?= $opt["root"]; ?>";
        Z.Request.rootHost = "<?= $opt["request"]->getRoot(); ?>";
        Z.Request.absRoot = "<?= $opt["absRoot"]; ?>";
        //TODO: make this better
        /*Z.Lang.addElement = "<?php $opt["lang"]("form_add_element"); ?>",
        Z.Lang.submit = "<?php $opt["lang"]("form_submit") ?>",
        Z.Lang.saved = "<?php $opt["lang"]("form_saved") ?>",
        Z.Lang.saveError = "<?php $opt["lang"]("form_save_error") ?>",
        Z.Lang.unsaved = "<?php $opt["lang"]("form_unsaved_changes") ?>"*/
    </script>

    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

<?php } ?>

<?php 
/**
 * Call this to paste the essential body part of a page into the layout
 * @param object $opt Object holding options for rendering
 */
function essentialsBody($opt) { ?>
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