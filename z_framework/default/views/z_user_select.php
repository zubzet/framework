<?php function head($opt) { ?> <!-- File header -->

<?php } function body($opt) { ?> <!-- File body -->	
    <h2><?php $opt["lang"]("edit_user"); ?></h2>

    <div class="list-group">
      <?php foreach($opt["users"] as $user) { ?>
        <a href="<?php echo $opt["root"]. "z/edit_user/" . $user["id"]; ?>" class="list-group-item list-group-item-action"><?php echo $user["email"]; ?></a>
      <?php } ?>
    </div>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "edit_user" => "Benutzer bearbeiten",
        ], 
        "en" => [
            "edit_user" => "Edit user",
        ]
    ];
}
?>