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
									<a href="<?php echo $opt["link"]; ?>" target="_blank"><?php $opt["lang"]("button"); ?></a>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>

<?php }; $getLangArray = function() {
    return [
        "de_formal" => [
            "hello" => "Hallo",
			"text" => "denken Sie daran Ihre verfügbaren Zeiten bei Skill-DB einzutragen. Klicken Sie auf \"Zu Skill-DB\" um direkt zur SKill-DB weitergeleitet zu werden.",
			"button" => "Zu Skill-DB"
        ],
        "en" => [
            "hello" => "Hello",
            "text" => "remember to keep ypur available time frames at Skill-DB up to date. Click on \"Go to Skill-DB\" to get redirected to Skill-DB automatically.",
            "button" => "Go to Skill-DB"
        ]
    ];
} ?>