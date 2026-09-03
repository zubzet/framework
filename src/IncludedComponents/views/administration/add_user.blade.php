@extends($layout)

@section("content")
    <h2>
        <?= e(__("admin.add_user.title")) ?>
    </h2>

    <div id="create-user-form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "create-user-form",
            doReload: true,
        });

        form.createField({
            name: "email",
            type: "email",
            text: <?= json_encode(__("admin.add_user.email")) ?>,
            placeholder: "name@example.com"
        });

        form.createField({
            name: "password",
            type: "password",
            required: true,
            text: <?= json_encode(__("admin.add_user.password")) ?>,
            placeholder: "******",
        });

        $("label").addClass("mb-0");
    </script>
@endsection
