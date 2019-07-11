<?php function head($opt) { ?>

    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">

    <style>
        #no-match-message {
            color: red;
            text-align: left;
        }

        .do-not-have-margin {
            margin: 0;
        }
    </style>

<?php } function body($opt) { ?>

	<div class="row medium-7 large-5 align-center columns container-padded">
        <form action="" method="post" id="form">
            <div class="form-icons">
                <h4>Skill-DB - Password reset</h4>
                <div class="input-group">
                    <span class="input-group-label">
                        <i class="fa fa-key"></i>
                    </span>
                    <input name="password" id="input-password" class="input-group-field" type="password" placeholder="Password" minlength="4" required>
                </div>

                <div class="input-group">
                    <span class="input-group-label">
                        <i class="fa fa-key"></i>
                    </span>
                    <input id="input-password-repeat" class="input-group-field" type="password" placeholder="Confrm Password" minlength="4" required>
                </div>
                <div id="no-match-message" style="display: none">Passwords don't match!</div>

                <button id="button-reset" class="button expanded">Reset</button>
            </div>  
        </form>

    </div>
    
    <script>

        document.getElementById("input-password").addEventListener("change", e => { check(); });
        document.getElementById("input-password-repeat").addEventListener("change", e => { check(); });

        /*
        document.getElementById("button-reset").addEventListener("click", e => {

            var password = document.getElementById("input-password").value;

            fetch('', {
                method: "post",
                body: JSON.stringify({
                    password: password
                })
            });
        });*/

        function check() {
            var password = document.getElementById("input-password").value;
            var password2 = document.getElementById("input-password-repeat").value;

            document.getElementById("no-match-message").style.display = password == password2 ? "none" : "block";
            if (password != password2) {
                document.getElementById("button-reset").setAttribute("disabled", password != password2);
            } else {
                document.getElementById("button-reset").removeAttribute("disabled");
            }
        }

        /*
        $("#form").change(() => {
            var form = document.getElementById("form");
            $("#button-reset").attr("disabled", !form.checkValidity());
        });
        */
    </script>

<?php } ?>