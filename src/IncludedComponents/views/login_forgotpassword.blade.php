@extends($layout)

@section("head")
    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">

	<style>
		.login-error {
			color: red;
		}
	</style>
@endsection

@section("content")
		<div style="max-width: 1000px; margin: auto">
			<form onSubmit="return false;">

				<h2>Forgot password</h2>
				<div id="reset-error-label" class="text-danger"></div>

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
			Z.Presets.ForgotPassword("usernameemail", "reset-error-label", "<?= $opt["root"]; ?>login");
        }
    </script>
@endsection
