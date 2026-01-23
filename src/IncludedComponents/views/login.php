<?php 
/**
 * The login view
 */

return ["head" => function($opt) { ?>
	<link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">
<?php }, "body" => function($opt) { ?>

	<div style="max-width: 1000px; margin: auto">
		<h2>Login</h2>
		<form onSubmit="return false;">
			<div id="login-error-label" class="text-danger"></div>

			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-user"></i></span>
				</div>
				<input id="username" class="form-control" type="text" placeholder="Username">
			</div>

			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-key"></i></span>
				</div>
				<input id="password" class="form-control" type="password" placeholder="Password">
			</div>

			<button onclick="login();" class="btn btn-primary">Sign in</button>
			<a class="link" href="<?php echo $opt["root"]; ?>login/forgot_password">Forgot Password?</a>
			<a class="link" href="<?php echo $opt["root"]; ?>login/signup">Don't have a account?</a>
		</form>
	</div>

	<div class="loading" id="loading" style="display: none;">Loading&#8230;</div>

	<script>
		function login() {
			Z.Presets.Login("username", "password", "login-error-label", "");
		}
	</script>
<?php }]; ?>