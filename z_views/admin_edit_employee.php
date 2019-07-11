<?php function head($opt) { ?> <!-- File header -->

    <script>

        employeeId = null;

        $(function() {
            $("#select-employee").on('change', (e) => {
                selectId($("#select-employee").val());
            });
        });

        function selectId(id) {
            employeeId = id;

            var request = $.post({
                type: "POST",
                url: "",
                data: "Select=1&employee_id="+id
            });

            request.done((data) => {
                console.log(data);
                var obj = JSON.parse(data);

                $("#name").val(obj.data[0].name).prop("disabled", false);
                $("#first-name").val(obj.data[0].firstName).prop("disabled", false);
                $("#email").val(obj.data[0].email).prop("disabled", false);
                $("#tag").val(obj.data[0].tagId).prop("disabled", false);
                $("#language").val(obj.data[0].languageId).prop("disabled", false);
                $("#permissionLevel").val(obj.data[0].permissionLevel).prop("disabled", false);

                $("#button-save").prop("disabled", false);

                $(".select-space").remove();

                checkName();
                checkMail();
            });
        }

        <?php if ($opt["ref_save"]) {
            echo "selectId(".$opt["ref_save_userId"].")";
        } ?>
    </script>

<?php } function body($opt) { ?> <!-- File body -->	

    <div id="save-feedback" class="callout success" data-closable style="display: none">
            <span id="save-feedback-text">

            </span>
            <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

    <form method="post">

        <div class="row">
            <div class="medium-12 columns">
                <h5><?php $opt["lang"]("edit_employee"); ?></h5>
            </div>
        </div>

        <?php if ($opt["ref_save"]) { ?>
            <div class="callout success" data-closable>
                <?php echo "Saved $opt[ref_save_user] (".date("H:i:s", time()).")"; ?>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <div class="row">
            <div class="medium-12 columns">
                <label for="select-employee" name="employee_id"><?php $opt["lang"]("employee"); ?></label>
                <select id="select-employee" name="employee_id">
                    <option disabled selected value=""><?php $opt["lang"]("please_choose"); ?></option>
                    <?php 
                        foreach($opt["employees"] as $employee) {
                            echo '<option '.($employee["id"] == $opt["ref_save_userId"] ? "selected " : "").'value="'.$employee["id"].'">'.$employee["name"].'.'.$employee["firstName"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <h5 style="float: left;"><?php $opt["lang"]("personal_data"); ?></h5>
            </div>
        </div>

        <!-- Name -->
        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="first-name">
                    <?php $opt["lang"]("first_name"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="first-name" name="firstName" type="text" disabled required>
            </div>

            <div class="medium-6 small-12 columns">
                <label for="name">
                    <?php $opt["lang"]("last_name"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="name" name="name" type="text" disabled required>
                <span class="form-error" id="name-error">
                    <?php $opt["lang"]("error_name_taken") ?>
                </span>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns mb-2">
                <label for="email">
                    <?php $opt["lang"]("email"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input name="email" id="email" type="text" value="" disabled required>
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
                    <?php $opt["lang"]("internal_tag"); ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="tag" name="tag" class="input-group-field" disabled required>
                    <option disabled selected value=""><?php $opt["lang"]("please_choose"); ?></option>		
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
                <h5 style="float: left;"><?php $opt["lang"]("account_settings"); ?></h5>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <label for="permissionLevel">
                <?php $opt["lang"]("permission_level"); ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="permissionLevel" name="permissionLevel" class="input-group-field mb-2" required disabled>
                    <option disabled selected value="" class="select_space"><?php $opt["lang"]("please_choose"); ?></option>		
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
                    <?php $opt["lang"]("language"); ?>
                    <span style="color: red;">*</span>
                </label>
                <select class="input-group-field mb-2" name="languageId" id="language" required disabled>
                    <option disabled selected value="" class="select_space"><?php $opt["lang"]("please_choose"); ?></option>
                    <?php 
                        foreach($opt["languages"] as $lang) {
                            echo '<option value="'.$lang["id"].'">'.$lang["name"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>
        
    </form>
    <input id="button-save" type="submit" class="button" name="Save" value="<?php $opt["lang"]("save"); ?>" disabled>

    <script>

        document.getElementById("button-save").addEventListener('click', e => {

            document.getElementById("save-feedback").style.display = "";
            document.getElementById("save-feedback-text").innerHTML = "Saving...";

            $.ajax({
                type: "POST",
                url: "edit_employee/ajax/",
                data: {
                    firstName: document.getElementById("first-name").value,
                    name: document.getElementById("name").value,
                    email: document.getElementById("email").value,
                    tag: document.getElementById("tag").value,
                    permissionLevel: document.getElementById("permissionLevel").value,
                    language: document.getElementById("language").value,
                    employeeId: employeeId
                },
                success(data) {
                    var res = JSON.parse(data);
                    if (res.error) {
                        document.getElementById("save-feedback-text").innerHTML = "ERROR: " + res.error.message;
                    } else {
                        document.getElementById("save-feedback-text").innerHTML = "Saved!";
                    }
                    console.log(res);
                }
            });
        });

        document.getElementById("name").addEventListener("change", e => checkName());
        document.getElementById("first-name").addEventListener("change", e => checkName());

        function checkName() {
            var firstName = document.getElementById("first-name").value;
            var lastName = document.getElementById("name").value;

            fetch("unique/name_ex/" + firstName + "/" + lastName + "/" + employeeId).then(response => {
                return response.json();
            }).then(data => {
                console.log("Namecheck", data);
                
                if (data.result) {
                    document.getElementById("name").classList.remove("is-invalid-input");
                    document.getElementById("first-name").classList.remove("is-invalid-input");
                    document.getElementById("name-error").classList.remove("is-visible");
                } else {
                    document.getElementById("name").classList.add("is-invalid-input");
                    document.getElementById("first-name").classList.add("is-invalid-input");
                    document.getElementById("name-error").classList.add("is-visible");
                }

            });
        }

        function checkMail() {
            var email = document.getElementById("email").value;
            document.getElementById("email-error-2").classList.remove("is-visible");

            if (validateEmail(email)) {
                document.getElementById("email").classList.remove("is-invalid-input");
                document.getElementById("email-error").classList.remove("is-visible");

                fetch("unique/email_ex/" + btoa(email) + "/" + employeeId).then(response => {
                    return response.json();
                }).then(data => {
                    console.log("Mailcheck", data);
                    
                    if (data.result) {
                        document.getElementById("email").classList.remove("is-invalid-input");
                        document.getElementById("email-error-2").classList.remove("is-visible");
                    } else {
                        document.getElementById("email").classList.add("is-invalid-input");
                        document.getElementById("email-error-2").classList.add("is-visible");
                    }
                });

            } else {
                document.getElementById("email").classList.add("is-invalid-input");
                document.getElementById("email-error").classList.add("is-visible");
            }
        }

        document.getElementById("email").addEventListener("change", e => checkMail());

        function validateEmail(email) {
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    </script>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "edit_employee" => "Mitarbeiter Einstellungen Bearbeiten",
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
            "employee" => "Mitarbeiter",
            "error_name_taken" => "Diese Namenskombination is bereits vergeben.",
            "error_mail_taken" => "Diese Mail-Adresse wird bereits verwendet.",
            "error_mail_invalid" => "Diese Mail-Adresse ist nicht valide."
        ], 
        "en" => [
            "edit_employee" => "Edit Employee",
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
            "employee" => "Employee",
            "error_name_taken" => "This name combination is already taken.",
            "error_mail_taken" => "This mail address is already taken.",
            "error_mail_invalid" => "This mail address is not valid."
        ]
    ];
}
?>