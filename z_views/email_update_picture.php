<?php $body = function($opt) { ?>

<p><?php $opt["lang"]("hello"); ?> <?php echo $opt["firstName"]; ?>,</p>
<p><?php $opt["lang"]("text") ?></p>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
	<tbody>
		<tr>
			<td align="left">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0">
					<tbody>
						<tr>
							<td align="center">
								<a href="<?php echo $opt["link"] ?>" target="_blank"><?php $opt["lang"]("button"); ?></a>
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
            "text" => "denken Sie daran Ihr Profilbild bei Skill-DB auf dem aktuellen Stand zu halten. Klicken Sie auf \"Zu Skill-DB\" um direkt zur SKill-DB zu gelangen.",
            "button" => "Zu Skill-DB"
        ],
        "en" => [
            "hello" => "Hello",
            "text" => "remeber to keep your profile picture new and fresh, so everyone knows how gorgeous you look. Click on \"Go to Skill-DB\" to be brought to Skill-DB automatically.",
            "button" => "Go to Skill-DB"
        ]
    ];
} ?>