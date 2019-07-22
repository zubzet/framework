<?php 
/**
 * The user select view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	
    <h2><?php $opt["lang"]("edit_user"); ?></h2>

    <div class="list-group">
      <?php foreach($opt["users"] as $user) { ?>
        <a href="<?php echo $opt["root"]. "z/edit_user/" . $user["id"]; ?>" class="list-group-item list-group-item-action"><?php echo $user["email"]; ?></a>
      <?php } ?>
    </div>

<?php }, "lang" => [
        "de_formal" => [
            "edit_user" => "Benutzer bearbeiten",
        ], 
        "en" => [
            "edit_user" => "Edit user",
        ]
    ]
];
?>