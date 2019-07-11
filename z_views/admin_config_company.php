<?php function head($opt) { ?> <!-- File header -->

<?php } function body($opt) { ?> <!-- File body -->	
    <h1><?php $opt["lang"]("company_settings") ?></h1>

    <form method="post">

        <?php if ($opt["ref_save"]) { ?>
            <div class="callout success" data-closable>
                <?php echo "Saved (".date("H:i:s", time()).")"; ?>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <h5 style="float: left;"><?php echo $opt["lang"]("general_data") ?></h5>
            </div>
        </div>

        <div class="row">
            <div class="medium-12 small-12 columns">
                <label for="name">
                    <?php $opt["lang"]("name") ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($opt["company"]["name"], ENT_QUOTES); ?>">
            </div>
        </div>
        <hr>

        <h5><?php $opt["lang"]("address"); ?></h5>

        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="input-addr-country">
                    <?php $opt["lang"]("country"); ?>
                    <span style="color: red;">*</span>
                </label>
                <select id="input-addr-country" type="text" name="addr_country" required>
                    <option value="" disabled><?php $opt["lang"]("please_choose"); ?></option>
                    <?php 
                        foreach($opt["countries"] as $country) {
                            echo "<option ".($country["name"] == $opt["company"]["addr_country"] ? "selected" : "").">$country[name]</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-state">
                    <?php $opt["lang"]("address_state"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-state" type="text" name="addr_state" value="<?php echo htmlspecialchars($opt["company"]["addr_state"], ENT_QUOTES); ?>">
            </div>
        </div>

        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="input-addr-city">
                    <?php $opt["lang"]("city"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-city" type="text" name="addr_city" value="<?php echo htmlspecialchars($opt["company"]["addr_city"], ENT_QUOTES); ?>">
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-zip">
                    <?php $opt["lang"]("address_zip"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-zip" type="text" name="addr_zip" value="<?php echo htmlspecialchars($opt["company"]["addr_zip"], ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="input-addr-street">
                    <?php $opt["lang"]("street"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-street" type="text" name="addr_street" value="<?php echo htmlspecialchars($opt["company"]["addr_street"], ENT_QUOTES); ?>">
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-street-number">
                    <?php $opt["lang"]("street_number"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-street-number" type="text" name="addr_street_number" value="<?php echo htmlspecialchars($opt["company"]["addr_street_number"], ENT_QUOTES); ?>">
            </div>
        </div>

        <hr>
        <h5><?php $opt["lang"]("contact_information"); ?></h5>

        <div class="row columns">
            <label for="input-email">
                <?php $opt["lang"]("email"); ?>
                <span style="color: red;">*</span>
            </label>
            <input id="input-email" type="email" name="email" value="<?php echo htmlspecialchars($opt["company"]["email"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-web">
                <?php $opt["lang"]("web"); ?>
            </label>
            <input id="input-web" type="url" name="web" value="<?php echo htmlspecialchars($opt["company"]["web"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-tel">
                <?php $opt["lang"]("phone"); ?>
            </label>
            <input id="input-tel" type="text" name="phone" value="<?php echo htmlspecialchars($opt["company"]["phone"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-mobil">
                <?php $opt["lang"]("mobil"); ?>
            </label>
            <input id="input-mobil" type="text" name="mobile_phone" value="<?php echo htmlspecialchars($opt["company"]["mobile_phone"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-fax">
                <?php $opt["lang"]("fax"); ?>
            </label>
            <input id="input-fax" type="text" name="fax" value="<?php echo htmlspecialchars($opt["company"]["fax"], ENT_QUOTES); ?>">
        </div>

        <input type="submit" class="button" name="Save" value='<?php $opt["lang"]("save") ?>'>

    </form>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "company_settings" => "Firmen Einstellungen",
            "general_data" => "Generelle Daten",
            "name" => "Name",
            "email" => "Email",
            "address" => "Addresse",
            "country" => "Land",
            "address_state" => "Staat",
            "city" => "Stadt",
            "address_zip" => "PLZ",
            "street" => "Straße",
            "street_number" => "Hausnummer",
            "web" => "Website",
            "phone" => "Telefon",
            "mobile_phone" => "Handy",
            "fax" => "Fax",
            "contact_information" => "Kontakt Informationen",
            "save" => "Speichern"
        ], 
        "en" => [
            "company_settings" => "Company Settings",
            "general_data" => "General Data",
            "name" => "Name",
            "email" => "Email",
            "address" => "Address",
            "country" => "Country",
            "address_state" => "State",
            "city" => "City",
            "address_zip" => "ZIP (Postal Code)",
            "street" => "Street",
            "street_number" => "Street Number",
            "web" => "Website",
            "phone" => "Phone",
            "mobile_phone" => "Mobile",
            "fax" => "Fax",
            "contact_information" => "Contact Information",
            "save" => "Save"
        ]
    ];
}
?>