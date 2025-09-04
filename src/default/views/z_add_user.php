<?php return ["body" => function($opt) { ?>

    <h2>
        <?php $opt["lang"]("title"); ?>
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
            text: "<?php $opt["lang"]('email') ?>",
            placeholder: "name@example.com"
        });

        form.createField({
            name: "languageId",
            type: "select",
            required: true,
            text: "<?php $opt["lang"]('language') ?>",
            food: <?= $opt["languages"] ?>,
        });

        form.createField({
            name: "password",
            type: "password",
            required: true,
            text: "<?php $opt["lang"]('password') ?>",
            placeholder: "******",
        });

        $("label").addClass("mb-0");
    </script>

<?php }, "lang" => [
            "de_formal" => [
                "title" => "Benutzer erstellen",
                "email" => "Email",
                "language" => "Sprache",
                "please_choose" => "Bitte auswählen",
                "permission_level" => "Zugriffseinstellung",
                "password" => "Passwort",
                "save" => "Speichern"
            ],
            "en" => [
                "title" => "Add User",
                "email" => "Email",
                "please_choose" => "Please Choose",
                "permission_level" => "Permission Level",
                "language" => "Language",
                "password" => "Password",
                "save" => "Save"
            ]
        ]
    ];
?>