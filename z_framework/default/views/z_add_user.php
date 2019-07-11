<?php function head() { ?> <!-- File header -->


<?php } function body($opt) { ?> <!-- File body -->	

    <h2><?php $opt["lang"]("title"); ?></h2>

    <script>
        $(function() {
            var form = Z.Forms.create({doReload: true});
            form.createField({name: "email", type: "email", required: true, text: "<?php $opt["lang"]('email') ?>", placeholder: "name@example.com"});
            form.createField({name: "languageId", type: "select", required: true, text: "<?php $opt["lang"]('language') ?>"}).feedData(<?php echo $opt["languages"] ?>);
            $("#create-user-form").append(form.dom);
        });
    </script>

    <div id="create-user-form"></div>
<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "title" => "Benutzer erstellen",
                "email" => "Email",
                "language" => "Sprache",
                "please_choose" => "Bitte auswählen",
                "permission_level" => "Zugriffseinstellung",
                "save" => "Speichern"
            ], 
            "en" => [
                "title" => "Add User",
                "email" => "Email",
                "please_choose" => "Please Choose",
                "permission_level" => "Permission Level",
                "language" => "Language",
                "save" => "Save"
            ]
        ];
    }
?>