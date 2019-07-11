<!-- "name", "firstName", "register_link" -->

<?php $body = function($opt) { ?>

	<p><?php $opt["lang"]("hello"); ?> <?php echo $opt["firstName"]; ?>,</p>
	<p><?php echo $opt["user"]["firstName"] . " " . $opt["user"]["name"] . " "; $opt["lang"]("created_acc"); ?></p>
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
		<tbody>
			<tr>
				<td align="left">
					<table role="presentation" border="0" cellpadding="0" cellspacing="0">
						<tbody>
							<tr>
								<td align="center">
									<a href="<?php echo $opt["register_link"] ?>" target="_blank"><?php $opt["lang"]("activate_account"); ?></a>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>
	<p><?php $opt["lang"]("ignore_email"); ?></p>

<?php }; $getLangArray = function() {
    return [
        "de_formal" => [
            "hello" => "Hallo",
            "created_acc" => "hat einen SKDB Account für Sie erstellt. Aktivieren Sie Ihren Account indem Sie auf \"Account aktivieren\" klicken.",
            "ignore_email" => "Ignorieren Sie diese Mail, falls Sie die Person nicht kennen, welche diese angefordert hat und klicken Sie nicht den Button an!",
            "activate_account" => "Account aktivieren"
		],
		"en" => [
			"hello" => "Hello",
			"created_acc" => "has created a SKDB account for you. Please click on \"Activate your account\" to activate and be able to use it.",
			"ignore_email" => "If you do not know the person, who has requsted this email, please do not click the button and simply ignore it!",
			"activate_account" => "Activate your account"
		]
	];
} ?>