<?php 
/**
 * This file holds essential parts fot the framework that will be pasted into the layout.
 */

/**
 * Call this to paste the essential head part of a page into the layout
 * @param object $opt Object holding options for rendering
 */
function essentialsHead($opt) { ?>
    <script src="<?php echo $opt["root"] ?>assets/js/jquery.min.js"></script>
    <script src="<?php $opt["generateResourceLink"]("assets/js/Z.js") ?>"></script>
    <script src="<?php echo $opt["root"] ?>assets/js/bootstrap.min.js"></script>
    <script src="<?php $opt["generateResourceLink"]("assets/js/bs-custom-file-input.js"); ?>"></script>
    <link href="<?php echo $opt["root"] ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $opt["root"] ?>assets/css/font-awesome/all.min.css" rel="stylesheet">
    <script>
        Z.Request.rootPath = "<?php echo $opt["root"]; ?>";
        //ToDo: make this better
        /*Z.Lang.addElement = "<?php $opt["lang"]("form_add_element"); ?>",
        Z.Lang.submit = "<?php $opt["lang"]("form_submit") ?>",
        Z.Lang.saved = "<?php $opt["lang"]("form_saved") ?>",
        Z.Lang.saveError = "<?php $opt["lang"]("form_save_error") ?>",
        Z.Lang.unsaved = "<?php $opt["lang"]("form_unsaved_changes") ?>"*/
    </script>
<?php } ?>

<?php 
/**
 * Call this to paste the essential body part of a page into the layout
 * @param object $opt Object holding options for rendering
 */
function essentialsBody($opt) { ?>
    <!-- TOKEN EXPIRED MESSAGE -->
    <?php if($opt["user"]->isLoggedIn) { ?>
    <style>
        .tokenExpired {
            position: fixed;
            top: 0px;
            left: 0px;
            width: 100%;
            height: 100%;
            background: white;
        }

        .tokenExpired iframe {
            border: none;
        }
    </style>

    <div class="tokenExpired invisible" id="tokenExpired">
        <h1 class="text-center"><?php $opt["lang"]("token_expired_heading"); ?></h1>
        <p class="lead text-center"><?php $opt["lang"]("token_expired_explanation"); ?></p>
        <iframe id="loginFrame" class="login-frame" width="100%"></iframe>
    </div>
    
    <script>
        var timedOut = false;

        var iframe = document.getElementById("loginFrame");
        iframe.onload = function() {
            if (iframe.contentWindow.document.body) {
                iframe.height = (iframe.contentWindow.document.body.scrollHeight + 10) + "px";
            }
        }

        var token_expired_callback = setInterval(function() {
            if (!timedOut) {
                if(document.cookie.indexOf("z_login_token") < 0) {
                    timedOut = true;
                    $("#tokenExpired").removeClass("invisible");
                    $("#loginFrame").attr("src", '<?php echo $opt["root"]; ?>login?noLayout=true');
                }
            }
            if (timedOut) {
                if(document.cookie.indexOf("z_login_token") >= 0) {
                    timedOut = false;
                    $("#tokenExpired").addClass("invisible");
                }
            }
        }, 1000);
    </script>
    <!-- TOKEN EXPIRED MESSAGE -->
<?php } } ?>