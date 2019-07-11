<?php function head($opt) { ?>
	<link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">
	<style>
		.login-error {
			color: red;
		}
		a { 
			color: black;
			font-weight: bold;
			text-decoration: underline;
		}
	</style>
<?php } function body($opt) { ?>
	<div class="row medium-7 large-5 align-center columns container-padded">
		<form onSubmit="return false;">
			<div class="form-icons">
				<h4>Skill-DB - login</h4>
				<div id="login-error-label" class="login-error"></div>
				<div class="input-group">
					<span class="input-group-label">
						<i class="fa fa-user"></i>
					</span>
					<input id="username" class="input-group-field" type="text" placeholder="Username">
				</div>
				<div class="input-group">
					<span class="input-group-label">
						<i class="fa fa-key"></i>
					</span>
					<input id="password" class="input-group-field" type="password" placeholder="Password">
				</div>
			</div>
			<button onClick="login();" class="button expanded">Sign in</button>
			<a href="<?php echo $opt["root"]; ?>login/forgot_password<?php if($opt["noLayout"] === "true") echo "?noLayout=true"; ?>">Forgot Password?</a>
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
<?php } ?>