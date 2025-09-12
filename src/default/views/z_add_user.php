<?php return ["body" => function($opt) { ?>

    <h2>
        Add User
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
            required: true,
            text: "Email",
            placeholder: "name@example.com"
        });

        form.createField({
            name: "languageId",
            type: "select",
            required: true,
            text: "Language",
            food: <?= $opt["languages"] ?>,
        });

        form.createField({
            name: "password",
            type: "password",
            required: true,
            text: "Password",
            placeholder: "******",
        });

        $("label").addClass("mb-0");
    </script>

<?php }]; ?>
