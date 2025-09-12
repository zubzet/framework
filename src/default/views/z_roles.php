<?php 
/**
 * The role editor view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputName = form.createField({name: "name", type: "name", text: "Name", value: "<?php echo $opt["name"]; ?>"});

            form.addSeperator();

            var ced = form.createCED({
                name: "permissions",
                text: "Permissions",
                compact: true,
                fields: [
                    { name: "name", type: "text", text: "Permission", width: 11, compact: true}
                ],
                value: <?php echo $opt["permissions"]; ?>
            });

            form.createActionButton("Delete role", "btn-danger", function() {
                if (confirm("Do you really want to delete this role?")) {
                    Z.Request.action("delete", {}, function() {
                        window.location.replace("<?php echo $opt["root"]. "z/roles/" ?>");
                    });
                }
            });
        });

    </script>

    <h2>Roles</h2>

    <div id="form"></div>

<?php }]; ?>
