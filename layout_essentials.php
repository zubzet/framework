<?php 
/**
 * This file holds essential parts fot the framework that will be pasted into the layout.
 */

/**
 * Call this to paste the essential head part of a page into the layout
 * @param object $opt Object holding options for rendering
 */
function essentialsHead($opt) { ?>
    <script src="https://pagecdn.io/lib/jquery/3.4.1/jquery.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="<?php echo $opt["root"] ?>assets/js/Z.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css" rel="stylesheet">
    <script>
        Z.Lang.addElement = "<?php $opt["lang"]("form_add_element"); ?>",
        Z.Lang.submit = "<?php $opt["lang"]("form_submit") ?>",
        Z.Lang.saved = "<?php $opt["lang"]("form_saved") ?>",
        Z.Lang.saveError = "<?php $opt["lang"]("form_save_error") ?>",
        Z.Lang.unsaved = "<?php $opt["lang"]("form_unsaved_changes") ?>"
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