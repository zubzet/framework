<!-- "name", "firstName", "register_link" -->

<?php $body = function($opt) { ?>

<p><?php $opt["lang"]("hello"); ?> <?php echo $opt["firstName"]; ?>,</p>
<p><?php $opt["lang"]("text"); ?></p>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
	<tbody>
		<tr>
			<td align="left">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td align="center">
								<a href="<?php echo $opt["reset_link"] ?>" target="_blank"><?php $opt["lang"]("button_reset"); ?></a>
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
			"text" => "Ihr Passwort für SKDB wurde zurückgesetzt. Klicken sie auf \"Password zurücksetzen\" um ein neues festzulegen.",
			"ignore_email" => "Ignorieren Sie diese Mail, falls Sie die Person nicht kennen, welche diese angefordert hat und klicken Sie nicht den Button an!",
			"button_reset" => "Passwort zurücksetzen"
		],
		"en" => [
			"hello" => "Hello",
			"text" => "your password has been reset. Please click on \"Reset password\" to choose a new one.",
			"ignore_email" => "If you do not know the person, who has requsted this email, please do not click the button and simply ignore it!",
			"button_reset" => "Reset password"
		]
	];
} ?>