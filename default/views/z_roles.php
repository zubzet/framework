<?php 
/**
 * The role editor view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputName = form.createField({name: "name", type: "name", text: "<?php $opt["lang"]("name"); ?>", value: "<?php echo $opt["name"]; ?>"});

            form.addSeperator();

            var ced = form.createCED({
                name: "permissions",
                text: "<?php $opt["lang"]("permissions"); ?>",
                compact: true,
                fields: [
                    { name: "name", type: "text", text: "<?php $opt["lang"]("permission"); ?>", width: 11, compact: true}
                ],
                value: <?php echo $opt["permissions"]; ?>
            });

            form.createActionButton("<?php $opt["lang"]("delete"); ?>", "btn-danger", function() {
                if (confirm("<?php $opt["lang"]("delete_confirm"); ?>")) {
                    Z.Request.action("delete", {}, function() {
                        window.location.replace("<?php echo $opt["root"]. "z/roles/" ?>");
                    });
                }
            });
        });

    </script>

    <h2><?php $opt["lang"]("roles"); ?></h2>

    <div id="form"></div>

<?php }, "lang" => [
        "de_formal" => [
            "roles" => "Rollen",
            "name" => "Name",
            "permissions" => "Berechtigungen",
            "permission" => "Berechtigung",
            "delete_confirm" => "Möchten Sie wirklich die Rolle löschen?",
            "delete" => "Rolle Löschen"
        ], 
        "en" => [
            "roles" => "Roles",
            "name" => "Name",
            "permissions" => "Permissions",
            "permission" => "Permission",
            "delete_confirm" => "Do you really want to delete this role?",
            "delete" => "Delete role"
        ]
    ]
];
?>