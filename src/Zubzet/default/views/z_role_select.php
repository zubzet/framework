<?php 
/**
 * The view to create or select a role to edit. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <script>
        $(function() {
            $("#create-group").click(() => {
                if (confirm("<?php $opt["lang"]("confirm_create"); ?>")) {
                    Z.Request.action("create", {}, function(response) {
                        window.location.href = "<?php echo $opt["root"]. "z/roles/" ?>" + response.roleId;
                    });
                }
            });
        });
    </script>

    <h2><?php $opt["lang"]("edit_role"); ?></h2>

    <div class="list-group">
      <?php foreach($opt["roles"] as $role) { ?>
        <a href="<?php echo $opt["root"]. "z/roles/" . $role["id"]; ?>" class="list-group-item list-group-item-action"><?php echo $role["name"]; ?></a>
      <?php } ?>
    </div>

    <button class="btn btn-primary mt-2" id="create-group"><?php $opt["lang"]("create_role"); ?></button>

<?php }, "lang" => [
        "de_formal" => [
            "edit_role" => "Rollen",
            "create_role" => "Rolle erstellen",
            "confirm_create" => "Wollen Sie wirklich eine neue Rolle anlegen?"
        ], 
        "en" => [
            "edit_role" => "Roles",
            "create_role" => "Create role",
            "confirm_create" => "Do you really want to create a new role?"
        ]
    ]
];
?>