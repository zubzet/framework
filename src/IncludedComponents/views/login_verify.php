<?php 
/**
 * View for mail verification feedback
 */

return ["head" => function($opt) { ?>
	<link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/loadCircle.css">
	<style>
		.login-error {
			color: red;
		}
	</style>
<?php }, "body" => function($opt) { ?>

  <div style="max-width: 1000px; margin: auto; margin-top: 25vh">
    <?php if ($opt["success"]) { ?>
        <div>Your email was verified! You now can log in!</div>
    <?php } else { ?>
        <div class="mb-2">
            You are missing the verification mail? Send Again!
            <form action="" method="POST">
                <div class="input-group">
                    <input name="email" id="input-email" class="form-control" type="email" placeholder="Your Email">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-primary">Send</button>
                    </div>
                </div>
            </form>
        </div>
    <?php } ?>
		<a href="<?php echo $opt["root"].$opt["login"]; ?>" class="btn btn-primary">To the login</a>
	</div>
<?php }]; ?>