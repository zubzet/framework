@extends($layout)

@section("content")
    <h2>
        Add Organization
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
            text: "Name",
            placeholder: "Example Company",
        });

        form.createField({
            name: "create_group",
            type: "checkbox",
            text: "Create a permission group",
            hint: "Creates a group named after the organization, suffixed with _Group. Every user assigned to the organization becomes a member of it.",
        });
    </script>
@endsection
