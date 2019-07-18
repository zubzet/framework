<?php return ["head" => function ($opt) { ?> <!-- File header -->


<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <h2><?php $opt["lang"]("title"); ?></h2>

    <script>
        $(function() {
            var form = Z.Forms.create({doReload: true});
            form.createField({name: "email", type: "email", required: true, text: "<?php $opt["lang"]('email') ?>", placeholder: "name@example.com"});
            form.createField({name: "languageId", type: "select", required: true, text: "<?php $opt["lang"]('language') ?>"}).feedData(<?php echo $opt["languages"] ?>);
            form.createField({name: "password", type: "password", required: false, text: "<?php $opt["lang"]('password') ?>", placeholder: "******"});
            $("#create-user-form").append(form.dom);
        });
    </script>

    <div id="create-user-form"></div>
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