
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
                if(document.cookie.indexOf("skdb_login_token") < 0) {
                    timedOut = true;
                    $("#tokenExpired").removeClass("invisible");
                    $("#loginFrame").attr("src", '<?php echo $opt["root"]; ?>?noLayout=true');
                }
            }
            if (timedOut) {
                if(document.cookie.indexOf("skdb_login_token") >= 0) {
                    timedOut = false;
                    $("#tokenExpired").addClass("invisible");
                }
            }
        }, 1000);
    </script>
<?php } ?>
<!-- TOKEN EXPIRED MESSAGE -->