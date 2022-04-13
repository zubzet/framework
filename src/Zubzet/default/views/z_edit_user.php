<?php 
/**
 * The edit user view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputEmail = form.createField({name: "email", type: "email", text: "<?php $opt["lang"]("email"); ?>", value: "<?php echo $opt["email"]; ?>"});
            var inputLanguage = form.createField({name: "languageId", type: "select", text: "<?php $opt["lang"]("language"); ?>", value: "<?php echo $opt["language"]; ?>", food: <?php echo $opt["languages"]; ?>});

            form.addSeperator();

            var ced = form.createCED({
                name: "roles",
                text: "<?php $opt["lang"]("roles"); ?>",
                compact: true,
                fields: [
                    { name: "role", type: "select", text: "<?php $opt["lang"]("role"); ?>", food: <?php echo $opt["roles"]; ?>, compact: true, width: 11 }
                ],
                value: <?php echo $opt["user_roles"]; ?>
            });

            form.createActionButton("<?php $opt["lang"]("login_as"); ?>", "btn-secondary", function() {
                window.location.href = "<?php echo $opt["root"] . "z/login_as/" . $opt["userId"] ?>";
            });
        });

    </script>

    <h2><?php $opt["lang"]("edit_user"); ?></h2>

    <div id="form"></div>

<?php }, "lang" => [
        "de_formal" => [
            "edit_user" => "Benutzer Bearbeiten",
            "first_name" => "Vorname",
            "last_name" => "Nachname",
            "personal_data" => "Persönliche Daten",
            "email" => "Email",
            "internal_tag" => "Internal tag",
            "language" => "Sprache",
            "save" => "Speichern",
            "please_choose" => "Bitte auswählen",
            "account_settings" => "Accounteinstellungen",
            "permission_level" => "Zugriffseinstellung",
            "user" => "Benutzer",
            "role" => "Rolle",
            "roles" => "Gruppen",
            "login_as" => "Einloggen als",
            "error_name_taken" => "Diese Namenskombination is bereits vergeben.",
            "error_mail_taken" => "Diese Mail-Adresse wird bereits verwendet.",
            "error_mail_invalid" => "Diese Mail-Adresse ist nicht valide."
        ], 
        "en" => [
            "edit_user" => "Edit user",
            "first_name" => "First Name",
            "last_name" => "Name",
            "personal_data" => "Personal Data",
            "email" => "Email",
            "internal_tag" => "Internal tag",
            "please_choose" => "Please Choose",
            "permission_level" => "Permission Level",
            "account_settings" => "Account settings",
            "language" => "Language",
            "save" => "Save",
            "user" => "User",
            "role" => "Role",
            "roles" => "Roles",
            "login_as" => "Login as",
            "error_name_taken" => "This name combination is already taken.",
            "error_mail_taken" => "This mail address is already taken.",
            "error_mail_invalid" => "This mail address is not valid.",
        ]
    ]
];
?>