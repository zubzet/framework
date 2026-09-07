@extends($layout)

@section("head")
 <!-- File header -->
@endsection

@section("content")
 <!-- File body -->	

    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputName = form.createField({name: "name", type: "name", text: <?= json_encode(__("admin.roles.name")) ?>, value: "<?php echo $opt["name"]; ?>"});

            form.addSeperator();

            var ced = form.createCED({
                name: "permissions",
                text: <?= json_encode(__("admin.roles.permissions")) ?>,
                compact: true,
                fields: [
                    { name: "name", type: "text", text: <?= json_encode(__("admin.roles.permission")) ?>, width: 11, compact: true}
                ],
                value: <?php echo $opt["permissions"]; ?>
            });

            form.createActionButton(<?= json_encode(__("admin.roles.delete")) ?>, "btn-danger", function() {
                if (confirm(<?= json_encode(__("admin.roles.delete_confirm")) ?>)) {
                    Z.Request.action("delete", {}, function() {
                        window.location.replace("<?php echo $opt["root"]. "z/roles/" ?>");
                    });
                }
            });
        });

    </script>

    <h2><?= e(__("admin.roles.title")) ?></h2>

    <div id="form"></div>
@endsection
