<?php function head() { ?> <!-- File header -->


<?php } function body($opt) { ?> <!-- File body -->	

    <form method="post" id="form">

        <div id="save-feedback" class="callout success" data-closable style="display: none">
            <span id="save-feedback-text">

            </span>
            <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <h5 style="float: left;"><?php echo $opt["lang"]("personal_data") ?></h5>
            </div>
        </div>

        <!-- Name -->
        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="name">
                    <?php $opt["lang"]("first_name") ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="first-name" name="name" type="text" required>
                <span class="form-error" id="name-error">
                    <?php $opt["lang"]("error_name_taken") ?>
                </span>
            </div>
            
            <div class="medium-6 small-12 columns">
                <label for="first-name">
                    <?php $opt["lang"]("last_name") ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="name" name="firstName" type="text" required>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns mb-2">
                <label for="email">
                    <?php $opt["lang"]("email") ?>
                    <span style="color: red;">*</span>
                </label>
                <input name="email" id="email" type="text" value="" required>
                <span class="form-error" id="email-error">
                    <?php $opt["lang"]("error_mail_invalid") ?>
                </span>
                <span class="form-error" id="email-error-2">
                    <?php $opt["lang"]("error_mail_taken") ?>
                </span>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <label for="tag">
                    <?php $opt["lang"]("internal_tag") ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="tag" name="tag" class="input-group-field" required>
                    <option disabled selected value=""><?php $opt["lang"]("please_choose") ?></option>		
                    <?php
                        foreach ($opt["tags"] as $tag) {
                            echo '<option value="'.$tag["id"].'">'.$tag["name"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <h5 style="float: left;"><?php $opt["lang"]("account_settings") ?></h5>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <label for="permissionLevel">
                    <?php $opt["lang"]("permission_level") ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="permissionLevel" name="permissionLevel" class="input-group-field mb-2" required>
                    <option disabled selected value=""><?php $opt["lang"]("please_choose") ?></option>
                    <?php
                        foreach($opt["permissionNames"] as $perm) {
                            echo '<option value="'.$perm["value"].'">'.$perm["name"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="medium-12 small-12 columns">
                <label for="language">
                    <?php $opt["lang"]("language") ?>
                    <span style="color: red;">*</span>
                </label>
                <select class="input-group-field mb-2" name="language" id="language" required>
                    <option disabled selected value=""><?php $opt["lang"]("please_choose") ?></option>
                    <?php 
                        foreach($opt["languages"] as $lang) {
                            echo '<option value="'.$lang["id"].'">'.$lang["name"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>
    </form>
    
    <input type="submit" class="button" id="submit" name="Save" value='<?php $opt["lang"]("save") ?>' disabled>

    <script>

        $("#form").change(() => {
            console.log("Form changed");
            var form = document.getElementById("form");
            $("#submit").attr("disabled", !form.checkValidity());
        });

        document.getElementById("submit").addEventListener("click", e => {
            document.getElementById("save-feedback").style.display = "";
            document.getElementById("save-feedback-text").innerHTML = "Saving...";

            $.ajax({
                type: "POST",
                url: "add_employee/ajax/",
                data: {
                    firstName: document.getElementById("first-name").value,
                    name: document.getElementById("name").value,
                    email: document.getElementById("email").value,
                    tag: document.getElementById("tag").value,
                    permissionLevel: document.getElementById("permissionLevel").value,
                    language: document.getElementById("language").value
                },
                success(data) {
                    document.getElementById("save-feedback").style.display = "";
                    document.getElementById("save-feedback-text").innerHTML = "Saved!";
                    console.log(data);
                    checkName();
                    checkEmail();
                    $("#submit").attr("disabled", !form.checkValidity());
                }
            });
        });

        document.getElementById("name").addEventListener("change", e => checkName());
        document.getElementById("first-name").addEventListener("change", e => checkName());

        function checkName() {
            var firstName = document.getElementById("first-name").value;
            var lastName = document.getElementById("name").value;

            fetch("unique/name/" + firstName + "/" + lastName).then(response => {
                return response.json();
            }).then(data => {
                console.log(data);
                
                if (data.result) {
                    document.getElementById("name").classList.remove("is-invalid-input");
                    document.getElementById("first-name").classList.remove("is-invalid-input");
                    document.getElementById("name-error").classList.remove("is-visible");
                    document.getElementById("first-name").setCustomValidity("");
                } else {
                    document.getElementById("name").classList.add("is-invalid-input");
                    document.getElementById("first-name").classList.add("is-invalid-input");
                    document.getElementById("name-error").classList.add("is-visible");
                    document.getElementById("first-name").setCustomValidity("Invalid");
                }

            });
        }

        function checkEmail() {
            var emailInput = document.getElementById("email");
            var email = emailInput.value;

            document.getElementById("email-error-2").classList.remove("is-visible");

            if (validateEmail(email)) {
                emailInput.setCustomValidity("");
                document.getElementById("email").classList.remove("is-invalid-input");
                document.getElementById("email-error").classList.remove("is-visible");

                fetch("unique/email/" + btoa(email)).then(response => {
                    return response.json();
                }).then(data => {
                    console.log(data);
                    
                    if (data.result) {
                        document.getElementById("email").classList.remove("is-invalid-input");
                        document.getElementById("email-error-2").classList.remove("is-visible");
                        emailInput.setCustomValidity("");
                    } else {
                        document.getElementById("email").classList.add("is-invalid-input");
                        document.getElementById("email-error-2").classList.add("is-visible");
                        emailInput.setCustomValidity("Invalid");
                    }
                });

            } else {
                document.getElementById("email").classList.add("is-invalid-input");
                document.getElementById("email-error").classList.add("is-visible");
            }
        }

        document.getElementById("email").addEventListener("change", e => checkEmail());

        function validateEmail(email) {
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    </script>

<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "first_name" => "Vorname",
                "last_name" => "Nachname",
                "personal_data" => "Persönliche Daten",
                "email" => "Email",
                "internal_tag" => "Internal tag",
                "language" => "Sprache",
                "save" => "Speichern",
                "please_choose" => "Bitte auswählen",
                "account_settings" => "Accounteinstellungen",
                "permission_level" => "Zugriffseinstellung"
            ], 
            "en" => [
                "first_name" => "First Name",
                "last_name" => "Last Name",
                "personal_data" => "Personal Data",
                "email" => "Email",
                "internal_tag" => "Internal tag",
                "please_choose" => "Please Choose",
                "permission_level" => "Permission Level",
                "account_settings" => "Account settings",
                "language" => "Language",
                "save" => "Save",
                "error_name_taken" => "This name combination does already exists.",
                "error_mail_taken" => "This mail is already in the system",
                "error_mail_invalid" => "This is not a valid mail address"
            ]
        ];
    }
?>