<?php 
/**
 * The register view
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
		<h2>Sign up</h2>
		<form onSubmit="return false;">
			<div id="login-error-label" class="login-error"></div>

			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-user"></i></span>
				</div>
				<input id="username" class="form-control" type="email" placeholder="your@email.tld" autocomplete="new-password">
			</div>

			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-key"></i></span>
				</div>
				<input id="password" class="form-control" type="password" placeholder="Password" autocomplete="new-password">
			</div>

			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<span class="input-group-text"><i class="fa fa-key"></i></span>
				</div>
				<input id="password-confirm" class="form-control" type="password" placeholder="Confirm Password" autocomplete="new-password">
			</div>

			<button onclick="register();" class="btn btn-primary">Sign Up</button>
			<a class="link" href="<?php echo $opt["root"]; ?>login/">Login?</a>
		</form>
	</div>

	<div class="loading" id="loading" style="display: none;">Loading&#8230;</div>

	<script>
		function register() {
			Z.Presets.Signup("username", "password", "password-confirm", "login-error-label", "<?php echo $opt["root"]; ?>");
		}
	</script>
	
<?php }]; ?>