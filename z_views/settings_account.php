<?php function head($opt) { ?> <!-- File header -->

    <script>

        function checkEmail(email) {
            $("#save_btn").attr("disabled", true); //Deactivate while checking

            fetch('<?php echo $opt["root"]; ?>settings/account/email/'+email).then(res => res.json()).then(json => {
                var inUse = json.emailExists;
                if (inUse) alert('<?php echo $opt["lang"]("email_in_use"); ?>');

                var validEmail = validateEmail(email);
                if(!validEmail) alert('<?php echo $opt["lang"]("email_not_valid"); ?>');

                var error = inUse || !validEmail;
                forcedDisable = error;
                $("#save_btn").attr("disabled", forcedDisable);
            });
        }
        
        function validateEmail(email) {
            var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }

        var forcedDisable = false;

        $(() => {
            $("form").change(() => {
                $("#save_btn").attr("disabled", forcedDisable);
            });
        });

    </script>

<?php } function body($opt) { ?> <!-- File body -->	

    <form id="form" method="post">

        <?php if ($opt["ref_save"] && !$opt["ref_save_error"] ) { ?>
            <div class="callout success" data-closable>
                <?php echo $opt["lang"]("saved") .' ('.date("H:i:s", time()).")"; ?>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?> 

        <h5 style="float: left;">
            <?php $opt["lang"]("email"); ?>
            <span style="color: red;">*</span>
        </h5>
        <div class="input-group">
            <input name="email" id="email" type="email" onChange="checkEmail(this.value);" class="input-group-field" value="<?php echo $opt["mail"]; ?>">
        </div>

        <div class="input-group">
            <label for="getNotifications_skills">
                <input name="notifications_skills" id="getNotifications_skills" type="checkbox" class="input-group-field" <?php echo $opt["notifications_skills"]; ?>>
                <?php $opt["lang"]("notification_skills"); ?>
            </label>
        </div>

        <div class="input-group">
            <label for="getNotifications_time">
                <input name="notifications_time" id="getNotifications_time" type="checkbox" class="input-group-field" <?php echo $opt["notifications_time"]; ?>>
                <?php $opt["lang"]("notification_times"); ?>
            </label>
        </div>

        <div class="input-group">
            <label for="getNotifications_pp">
                <input name="notifications_pp" id="getNotifications_pp" type="checkbox" class="input-group-field" <?php echo $opt["notifications_pp"]; ?>>
                <?php $opt["lang"]("notification_pp"); ?>
            </label>
        </div>

        <hr>

        <h5><?php $opt["lang"]("change_password"); ?></h5>
        <a href="<?php echo $opt["root"]; ?>settings/account/change_password" class="dark_link"><?php $opt["lang"]("change_password_link"); ?></a>

        <hr>

        <h5 style="float: left;">
            <?php $opt["lang"]("language"); ?>
            <span style="color: red;">*</span>
        </h5>
        <div class="input-group">
            <select id="language" class="input-group-field" name="language">
                <?php 
                    foreach($opt["languages"] as $lang) {
                        echo '<option '.($lang["id"] == $opt["language_id"] ? 'selected' : '').' value="'.$lang["id"].'">'.$lang["nativeName"].'</option>';
                    }
                ?>
            </select>
        </div>

        <input type="submit" id="save_btn" class="button" name="Save" disabled value="<?php $opt["lang"]("save"); ?>">
        <a href="<?php echo $opt["root"]; ?>settings/skills" class="button secondary" style="float: right; margin-left: 10px;"><?php $opt["lang"]("edit_times"); ?></a>
        <a href="<?php echo $opt["root"]; ?>settings/time" class="button secondary" style="float: right; margin-left: 10px;"><?php $opt["lang"]("edit_skills"); ?></a>
    
    </form>

<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "email" => "E-Mail-Adresse",
                "language" => "Sprache",
                "save" => "Speichern",
                "edit_times" => "Zu Ihren <b>zeitlichen</b> Angaben",
                "edit_skills" => "Zu Ihren <b>Fähigkeiten</b>s Angaben",
                "notification_skills" => "Erhalten Sie eine E-Mail-Benachrichtigung, um regelmäßig daran erinnert zu werden, Ihre <b>Fähigkeiten</b> regelmäßig zu aktualisieren.",
                "notification_times" => "Erhalten Sie eine E-Mail-Benachrichtigung, um regelmäßig daran erinnert zu werden, Ihre <b>Zeit</b> zu aktualisieren.",
                "notification_pp" => "Erhalten Sie eine E-Mail-Benachrichtigung, um regelmäßig daran erinnert zu werden, Ihr <b>Profilbild</b> zu aktualisieren.",
                "saved" => "Gespeichert",
                "change_password" => "Password ändern",
                "change_password_link" => "Mit der Hilfe dieses Links können Sie Ihr Passwort ändern.",
                "email_in_use" => "Diese E-Mail-Adresse wird bereits verwendet.",
                "email_not_valid" => "Die von Ihnen eingegebenen E-Mail-Adresse is keine E-Mail-Adresse."
            ],
            "en" => [
                "email" => "Email",
                "language" => "Language",
                "save" => "Save",
                "edit_times" => "Edit your times here",
                "edit_skills" => "Edit your skills here",
                "notification_skills" => "Get an email notification to regularly update your <b>skills</b>.",
                "notification_times" => "Get an email notification to regularly update your <b>time</b>.",
                "notification_pp" => "Get an email notification to regularly update your <b>profile picture</b>.",
                "saved" => "Saved",
                "change_password" => "Change your password",
                "change_password_link" => "You can change your password by following this link.",
                "email_in_use" => "This email is already being used.",
                "email_not_valid" => "The email you entered is not a valid email."
            ]
        ];
    } 
?>