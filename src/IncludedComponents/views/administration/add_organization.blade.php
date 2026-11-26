@extends($layout)

@section("content")
    <h2>
        <?= e(__("admin.add_organization.title")) ?>
    </h2>

    <div id="create-organization-form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "create-organization-form",
            doReload: true,
        });

        form.createField({
            name: "name",
            type: "text",
            required: true,
            text: <?= json_encode(__("admin.add_organization.name")) ?>,
            placeholder: <?= json_encode(__("admin.add_organization.name_placeholder")) ?>,
        });

        form.createField({
            name: "max_capacity",
            type: "number",
            text: <?= json_encode(__("admin.add_organization.max_capacity")) ?>,
            hint: <?= json_encode(__("admin.add_organization.max_capacity_hint")) ?>,
        });

        form.createField({
            name: "create_group",
            type: "checkbox",
            text: <?= json_encode(__("admin.add_organization.create_group")) ?>,
            hint: <?= json_encode(__("admin.add_organization.create_group_hint")) ?>,
        });
    </script>
@endsection
