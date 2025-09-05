<?php 
/**
 * The password reset view
 */

return ["head" => function($opt) { ?>

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

<?php }, "body" => function($opt) { ?>

	<div style="max-width: 1000px; margin: auto">
        <form action="" method="post" id="form">
            <div class="form-icons">
                <h2>Password reset</h2>

				<div class="input-group mb-2">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa fa-key"></i></span>
					</div>
					<input name="password" id="input-password" class="form-control" type="password" placeholder="Password" minlength="4" required>
                </div>
                
                <div class="input-group mb-2">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa fa-key"></i></span>
					</div>
					<input id="input-password-repeat" class="form-control" type="password" placeholder="Confrm Password" minlength="4" required>
				</div>
                <div id="no-match-message" style="display: none">Passwords don't match!</div>

                <button id="button-reset" class="btn btn-primary">Reset</button>
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

<?php }]; ?>