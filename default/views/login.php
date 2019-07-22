<?php 
/**
 * The login view
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
		<h2>Login</h2>
		<form onSubmit="return false;">
			<div id="login-error-label" class="login-error"></div>

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
			<a class="link" href="<?php echo $opt["root"]; ?>login/forgot_password<?php if($opt["noLayout"] === "true") echo "?noLayout=true"; ?>">Forgot Password?</a>
		</form>
	</div>

	<div class="loading" id="loading" style="display: none;">Loading&#8230;</div>

	<script>
		function login() {
			var input = document.getElementById("username").value;
			var password = document.getElementById("password").value;
			sendPost('<?php echo $opt["root"]; ?>login', 'name='+input+'&password='+password);
		}

		function sendPost(Purl, Pparams) {
			var http = new XMLHttpRequest();
			var url = Purl;
			var params = Pparams;
			http.open('POST', url, true);
			http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			http.onreadystatechange = function() {
				if(http.readyState == 4 && http.status == 200) {
					if (http.responseText == "successful") {
						$("#loading").show();
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						document.getElementById("login-error-label").innerHTML = http.responseText;
					}
				}
			}
			http.send(params);
		}

	</script>
<?php }]; ?>