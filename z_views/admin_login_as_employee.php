<?php function head($opt) { ?> <!-- File header -->

<?php } function body($opt) { ?> <!-- File body -->	

    <form method="post">

        <div class="row">
            <div class="medium-12 columns">
                <h1><?php $opt["lang"]("login_as_user"); ?></h1>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 columns">
                <label for="select-user" name="user_id"><?php $opt["lang"]("user"); ?></label>
                <select id="select-user" name="user_id" required>
                    <option disabled selected value=""><?php $opt["lang"]("please_choose"); ?></option>
                    <?php 
                        foreach($opt["users"] as $user) {
                            echo '<option '.($user["id"] == $opt["ref_save_userId"] ? "selected " : "").'value="'.$user["id"].'">'.$user["email"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <input type="submit" class="button" name="Save" value="<?php $opt["lang"]("login"); ?>">

        <div class="row">
            <div class="medium-12 columns">
                <h5><?php $opt["lang"]("explanation"); ?></h5>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 columns">
                <?php $opt["lang"]("explanation_text"); ?>
            </div>
        </div>

    </form>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "login_as_user" => "Als Mitarbeiter einloggen",
            "user" => "Mitarbeiter",
            "please_choose" => "Bitte auswählen",
            "login" => "Anmelden",
            "explanation" => "Erklärung",
            "explanation_text" => "Nach der Auswahl eines Mitarbeiters werden Sie als diese Person angemeldet. Ihre Zugriffsrechte wereden ebenfalls betroffen sein. Um zu Ihrem Konto zurückzukehren, melden Sie sich ab und wieder an."
        ], 
        "en" => [
            "login_as_user" => "Login as user",
            "user" => "user",
            "please_choose" => "Please choose",
            "login" => "Login",
            "explanation" => "Explanation",
            "explanation_text" => "After choosing an user, you will be logged in as this person. Your permission level will also be affected. To go back to your account, logout and login as you again."
        ]
    ];
}
?>