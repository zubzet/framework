@extends($layout)

@section("content")
 <!-- File body -->	
    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputEmail = form.createField({name: "email", type: "email", text: <?= json_encode(__("admin.edit_user.email")) ?>, value: "<?php echo $opt["email"]; ?>"});

            form.addSeperator();

            var ced = form.createCED({
                name: "roles",
                text: <?= json_encode(__("admin.edit_user.roles")) ?>,
                compact: true,
                fields: [
                    { name: "role", type: "select", text: <?= json_encode(__("admin.edit_user.role")) ?>, food: <?php echo $opt["roles"]; ?>, compact: true, width: 11 }
                ],
                value: <?php echo $opt["user_roles"]; ?>
            });

            form.createActionButton(<?= json_encode(__("admin.edit_user.login_as")) ?>, "btn-secondary", function() {
                window.location.href = "<?php echo $opt["root"] . "z/login_as/" . $opt["userId"] ?>";
            });

            form.addSeperator();

            var pced = form.createCED({
                name: "permissions",
                text: <?= json_encode(__("admin.edit_user.permissions")) ?>,
                compact: true,
                fields: [
                    { name: "name", type: "text", text: <?= json_encode(__("admin.edit_user.permission")) ?>, compact: true, width: 11 }
                ],
                value: <?php echo $opt["user_permissions"]; ?>
            });

        });

    </script>

    <h2><?= e(__("admin.edit_user.title")) ?></h2>

    <div id="form"></div>
@endsection
