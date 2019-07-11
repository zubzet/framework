<?php function head($opt) { ?>

    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">

	<style>
		.login-error {
			color: red;
		}
	</style>
<?php } function body($opt) { ?>

	<div class="row medium-7 large-5 align-center columns container-padded">
		<form onSubmit="return false;">
			<div class="form-icons">
				<h4>Forgot password</h4>
				<div id="login-error-label" class="login-error"></div>
				<div class="input-group">
					<span class="input-group-label">
						<i class="fa fa-user"></i>
					</span>
					<input id="usernameemail" class="input-group-field" type="text" placeholder="Username or email">
				</div>
			</div>
			<button onClick="check();" class="button expanded">Send me an email</button>
			<a class="link" href="<?php echo $opt["root"]; ?><?php if($opt["noLayout"] === "true") echo "?noLayout=true"; ?>">Back to the Login</a>
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

<?php } ?>