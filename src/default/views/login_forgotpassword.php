<?php 
/**
 * The forgot password view
 */

return ["head" => function($opt) { ?>

    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">

	<style>
		.login-error {
			color: red;
		}
	</style>
<?php }, "body" => function($opt) { ?>
		<div style="max-width: 1000px; margin: auto">
			<form onSubmit="return false;">

				<h2>Forgot password</h2>
				<div class="input-group mb-2">
					<div class="input-group-prepend">
						<span class="input-group-text"><i class="fa fa-user"></i></span>
					</div>
					<input id="usernameemail" class="form-control" type="text" placeholder="Username">
				</div>

				<button onClick="check();" class="btn btn-primary">Send me an email</button>
				<a class="link" href="<?php echo $opt["root"]; ?>login">Back to the Login</a>
			</form>
		</div>

		<div class="loading" id="loading" style="display: none;">Loading&#8230;</div>

    <script>
        function check() {
			const url = "<?php echo $opt["root"]; ?>login/forgot_password/check";
			$("#loading").show();
            sendPost(url, {"unameemail": document.getElementById("usernameemail").value});
        }

        function sendPost(url, params) {
            $.post(url, params).done((data) => {
				if(JSON.parse(data).result == "success") {
					$("#loading").hide(0, () => {
						alert("An email was sent. Please check your inbox.");
					});
                } else {
					$("#loading").hide();
                    document.getElementById("login-error-label").innerHTML = `Your account could not be found. Please try again.`;
                }
			});
			$("#loading").hide();
        }
    </script>

<?php }]; ?>