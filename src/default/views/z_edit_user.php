<?php return ["body" => function($opt) { ?> <!-- File body -->	
    <script>
        $(function() {
            var form = Z.Forms.create({dom: "form"});

            var inputEmail = form.createField({name: "email", type: "email", text: "Email", value: "<?php echo $opt["email"]; ?>"});
            var inputLanguage = form.createField({name: "languageId", type: "select", text: "Language", value: "<?php echo $opt["language"]; ?>", food: <?php echo $opt["languages"]; ?>});

            form.addSeperator();

            var ced = form.createCED({
                name: "roles",
                text: "Roles",
                compact: true,
                fields: [
                    { name: "role", type: "select", text: "Role", food: <?php echo $opt["roles"]; ?>, compact: true, width: 11 }
                ],
                value: <?php echo $opt["user_roles"]; ?>
            });

            form.createActionButton("Login as", "btn-secondary", function() {
                window.location.href = "<?php echo $opt["root"] . "z/login_as/" . $opt["userId"] ?>";
            });
        });

    </script>

    <h2>Edit user</h2>

    <div id="form"></div>
<?php }]; ?>
