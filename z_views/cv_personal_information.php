<?php function head($opt) { ?> <!-- File header -->

    <script>

        listEducation = [];
        listHistory = [];

        const deleteLog = [];

        $(() => {
            $("#button-add-history").click(() => { addHistory(); hintUnsaved(); });
            $("#button-add-education").click(() => { addEducation(); hintUnsaved(); });
            $("#button-submit").click(() => { sendData(); });

            var lastLanguage = null;
            $("#select-language").on("focus", () => {
                lastLanguage = $("#select-language").val();
            })
            $("#select-language").on('change', (e) => {
                var doSwitch = true;
                if (unsaved) {
                    doSwitch = confirm("<?php $opt["lang"]("confirm_switch"); ?>");
                }
                if (doSwitch) {
                    var value = e.target.value;
                    window.location.replace("<?php echo $opt["root"]; ?>cv/personal_information/" + value); //Hack (User relative path)
                } else {
                    $("#select-language").val(lastLanguage);
                }
            });

            $("#button-autofill-address").click(() => {
                $.get("retrieve_company_data", "", (data) => {
                    var obj = JSON.parse(data);
                    $("#input-addr-country").val(obj.addr_country);
                    $("#input-addr-state").val(obj.addr_state);
                    $("#input-addr-city").val(obj.addr_city);
                    $("#input-addr-zip").val(obj.addr_zip);
                    $("#input-addr-street").val(obj.addr_street);
                    $("#input-addr-street-number").val(obj.addr_street_number);
                });
            });

            $("#button-autofill-contact").click(() => {
                $.get("retrieve_company_data", "", (data) => {
                    var obj = JSON.parse(data);
                    $("#input-email").val(obj.email);
                    $("#input-web").val(obj.web);
                    $("#input-tel").val(obj.phone);
                    $("#input-mobil").val(obj.mobile_phone);
                    $("#input-fax").val(obj.fax);
                })
            });

            $("#form").on('change', () => {
                hintUnsaved();
            })

            <?php 
                foreach($opt["professional_history"] as $history) {
                    echo ("addHistory(".addcslashes($history["id"], '`').',`'.addcslashes($history["start"], '`').'`,`'.addcslashes($history["end"], '`').'`,`'.addcslashes($history["title"], '`').'`,`'.addcslashes($history["position"], '`').'`);');
                }
                //`
                foreach($opt["education"] as $edu) {
                    echo ("addEducation(".$edu["id"].',`'.addcslashes($edu["start"], '`').'`,`'.addcslashes($edu["graduation"], '`').'`,`'.addcslashes($edu["title"], '`').'`,`'.addcslashes($edu["description"], '`').'`);');
                }
                //`
            ?>

        });

        class Education {

            constructor(dbId, start, graduation, title, description) {
                this.dbId = dbId;
                this.removed = false;

                this.dom = $("<div class='row callout secondary'></div>");

                var blockDelete = $("<div class='medium-3 small-12 columns'></div>");
                var buttonDelete = $("<button class='button' type='button'><?php $opt["lang"]("graduation.delete"); ?></button>");
                blockDelete.append(buttonDelete);
                buttonDelete.click(this.remove.bind(this));

                var blockStart = $("<div class='medium-3 small-12 columns'><label><?php $opt["lang"]("graduation.start"); ?></label></div>");
                this.inputStart = $("<input type='date'>");
                blockStart.append(this.inputStart);
                this.inputStart.val(start);
                
                var blockGraduation = $("<div class='medium-3 small-12 columns'><label><?php $opt["lang"]("graduation.end"); ?> <span style=\"color: red;\">*</span></label></div>");
                this.inputGraduation = $("<input type='date' required>");
                blockGraduation.append(this.inputGraduation);
                this.inputGraduation.val(graduation);

                var blockTitle = $("<div class='medium-6 small-12 columns'><label><?php $opt["lang"]("graduation.title"); ?> <span style=\"color: red;\">*</span></label></div>");
                this.inputTitle = $("<input type='text' required>");
                blockTitle.append(this.inputTitle);
                this.inputTitle.val(title);

                var blockDescription = $("<div class='medium-12 small-12 columns'><label><?php $opt["lang"]("graduation.description"); ?> <span style=\"color: red;\">*</span></label></div>");  
                this.inputDescription = $("<input type='text'>");
                blockDescription.append(this.inputDescription);
                this.inputDescription.val(description);

                this.dom.append(blockStart);
                this.dom.append(blockGraduation);
                this.dom.append(blockTitle);
                this.dom.append(blockDescription);
                this.dom.append(blockDelete);
                this.dom.append("<div><hr></div>");
            }

            remove() {
                this.dom.css("display", "none");
                this.removed = true;
                hintUnsaved();
                deleteLog.push(this);
                doRemove();
            }

            undoRemove() {
                this.dom.css("display", "");
                this.removed = false;
            }

            getPostString(index) {
                if (this.removed) {
                    return `&education[${index}][change]=remove&education[${index}][id]=${this.dbId}`;
                }
                var text;
                if (this.dbId == -1) {
                    text = `&education[${index}][change]=add`;
                } else {
                    text = `&education[${index}][change]=edit`;
                }

                text += `&education[${index}][id]=${this.dbId}
                        &education[${index}][start]=${this.inputStart.val()}
                        &education[${index}][graduation]=${this.inputGraduation.val()}
                        &education[${index}][title]=<#decURI#>${encodeURIComponent(this.inputTitle.val())}
                        &education[${index}][description]=<#decURI#>${encodeURIComponent(this.inputDescription.val())}`;
                return text;
            }
        }

        class History {

            constructor(dbId, start, end, title, position) {
                this.dbId = dbId;
                this.removed = false;

                this.dom = $("<div class='row callout secondary'></div>");

                var blockDelete = $("<div class='medium-3 small-12 columns'></div>");
                var buttonDelete = $("<button class='button' type='button'><?php $opt["lang"]("history.delete"); ?></button>");
                blockDelete.append(buttonDelete);
                buttonDelete.click(this.remove.bind(this));

                var blockStart = $("<div class='medium-3 small-12 columns'><label><?php $opt["lang"]("history.start"); ?></label></div>");
                this.inputStart = $("<input type='date'>");
                blockStart.append(this.inputStart);
                this.inputStart.val(start);
                
                var blockGraduation = $("<div class='medium-3 small-12 columns'><label><?php $opt["lang"]("history.end"); ?></label></div>");
                this.inputGraduation = $("<input type='date'>");
                blockGraduation.append(this.inputGraduation);
                this.inputGraduation.val(end);

                var blockTitle = $("<div class='medium-6 small-12 columns'><label><?php $opt["lang"]("history.title"); ?> <span style=\"color: red;\">*</span></label></div>");
                this.inputTitle = $("<input type='text' required>");
                blockTitle.append(this.inputTitle);
                this.inputTitle.val(title);

                var blockPosition = $("<div class='medium-12 small-12 columns'><label><?php $opt["lang"]("history.position"); ?> <span style=\"color: red;\">*</span></label></div>");  
                this.inputPosition= $("<input type='text'>");
                blockPosition.append(this.inputPosition);
                this.inputPosition.val(position);

                this.dom.append(blockStart);
                this.dom.append(blockGraduation);
                this.dom.append(blockTitle);
                this.dom.append(blockPosition);
                this.dom.append(blockDelete);
                this.dom.append("<div><hr></div>");
            }

            remove() {
                this.dom.css("display", "none");
                this.removed = true;
                hintUnsaved();
                deleteLog.push(this);
                doRemove();
            }

            undoRemove() {
                this.dom.css("display", "");
                this.removed = false;
            }

            getPostString(index) {
                if (this.removed) {
                    return `&history[${index}][change]=remove&history[${index}][id]=${this.dbId}`;
                }
                var text;
                if (this.dbId == -1) {
                    text = `&history[${index}][change]=add`;
                } else {
                    text = `&history[${index}][change]=edit`;
                }

                text += `&history[${index}][id]=${this.dbId}`
                +`&history[${index}][start]=${this.inputStart.val()}`
                +`&history[${index}][end]=${this.inputGraduation.val()}`
                +`&history[${index}][title]=<#decURI#>${encodeURIComponent(this.inputTitle.val())}`
                +`&history[${index}][position]=<#decURI#>${encodeURIComponent(this.inputPosition.val())}`;
                return text;
            }
        }

        function addHistory(dbId = -1, start = "", end = "", title = "", position = "") {
            var history = new History(dbId, start.slice(0, 10), end.slice(0, 10), title, position);
            $("#table-history").append(history.dom);
            listHistory.push(history);
        }

        function addEducation(dbId = -1, start = "", graduation = "", title = "", description = "") {
            var education = new Education(dbId, start.slice(0, 10), graduation.slice(0, 10), title, description);
            $("#table-education").append(education.dom);
            listEducation.push(education);
        }

        function sendData() {

            var data = "Save=1";

            data += `&addr_country=<#decURI#>${encodeURIComponent($("#input-addr-country").val())}`;
            data += `&addr_state=<#decURI#>${encodeURIComponent($("#input-addr-state").val())}`;
            data += `&addr_city=<#decURI#>${encodeURIComponent($("#input-addr-city").val())}`;
            data += `&addr_zip=<#decURI#>${encodeURIComponent($("#input-addr-zip").val())}`;
            data += `&addr_street=<#decURI#>${encodeURIComponent($("#input-addr-street").val())}`;
            data += `&addr_street_number=<#decURI#>${encodeURIComponent($("#input-addr-street-number").val())}`;
            data += `&email=<#decURI#>${encodeURIComponent($("#input-email").val())}`;
            data += `&web=<#decURI#>${encodeURIComponent($("#input-web").val())}`;
            data += `&tel=<#decURI#>${encodeURIComponent($("#input-tel").val())}`;
            data += `&mobil=<#decURI#>${encodeURIComponent($("#input-mobil").val())}`;
            data += `&fax=<#decURI#>${encodeURIComponent($("#input-fax").val())}`;
            data += `&position=<#decURI#>${encodeURIComponent($("#input-position").val())}`;
            data += `&nationality=<#decURI#>${encodeURIComponent($("#input-nationality").val())}`;
            data += `&birthdate=<#decURI#>${encodeURIComponent($("#input-birthdate").val())}`;

            for (var i = 0; i < listEducation.length; i++) {
                data += listEducation[i].getPostString(i);
            }

            for (var i = 0; i < listHistory.length; i++) {
                data += listHistory[i].getPostString(i);
            }

            console.log(data);

            $.post({
                url: "",
                data: data,
                success(res) {
                    try {
                        var obj = JSON.parse(res);
                        console.log(res);
                        if (obj.result == "success") {
                            hintSaved();
                        } else {
                            alert('<?php $opt["lang"]("not_saved"); ?>');
                            console.error("send: " + data);
                            console.error("got: " + res);
                        }
                    } catch(e) {
                        alert('<?php $opt["lang"]("response_not_parsed"); ?>');
                        console.error(e);
                    }
                }
            });
        }

        unsaved = false;
        function hintUnsaved() {
            $("#button-submit").attr("disabled", false);
            $("#save-feedback").slideUp();
            $("#unsaved-feedback").fadeIn();
            $("#unsaved-spacer").slideDown();
            unsaved = true;
        }

        function hintSaved() {
            $("#button-submit").attr("disabled", true);
            $("#save-feedback").slideDown();
            $("#unsaved-feedback").fadeOut();
            $("#unsaved-spacer").hide();
            unsaved = false;
        }

        function undoRemove() {
            if (deleteLog.length) {
                deleteLog.pop().undoRemove();
                if (deleteLog.length == 0) {
                    $("#button-undo-remove").css("display", "none");
                }
            }
        }

        function doRemove() {
            $("#button-undo-remove").css("display", "");
            $("#undo-hint-bar").css("display", "");
            $("#undo-hint-bar-countdown").html("(5)");
            for (let i = 5; i > 0; i--) {
                setTimeout(() => {
                    $("#undo-hint-bar-countdown").html("(" + (5-i) + ")");
                }, i * 1000);
            }
            setTimeout(() => {
                $("#undo-hint-bar").slideUp();
            }, 6000);
        }
    </script>

    <style>
        .unsaved-fixed {
            position: fixed;
            z-index: 1;
        }

        .unsaved-callout {
            padding: 8px 32px;
        }

        .undo-hint-bar {
            margin-bottom: 0;
            left: 0px;
            bottom: 0;
            position: fixed;
            width: 100%;
            height: 70px;
        }
    </style>

<?php } function body($opt) { ?> <!-- File body -->	

    <div id="save-feedback" class="callout success" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_saved"); ?></span>
    </div>

    <div id="unsaved-feedback" class="unsaved-callout unsaved-fixed unsaved-callout callout warning" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_unsaved"); ?></span>
    </div>

    <div id="unsaved-spacer" class="unsaved-callout callout warning" data-closable style="display: none; opacity: 0">
        <span>Spaaace</span>
    </div>

    <form id="form" method="post">

        <?php if ($opt["ref_save"]) { ?>
            <div class="callout success" data-closable>
                <?php echo 'Saved ('.date("H:i:s", time()).")"; ?>
                <button class="close-button" aria-label="Dismiss alert" type="button" data-close>
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <h1><?php $opt["lang"]("personal_information"); ?></h1>

        <div class="row">
            <div class="medium-12 columns">
                <label for="select-language"><?php $opt["lang"]("language"); ?></label>
                <select id="select-language" name="language_id">
                    <?php 
                        foreach($opt["languages"] as $language) {
                            $is_selected = strtolower($language["value"]) == strtolower($opt["selected_lang"]);
                            echo '<option value="'.$language["value"].'" '. ($is_selected ? "selected" : "").' >'.$language["nativeName"].'</option>';
                        }
                    ?>
                </select>
            </div>
        </div>

        <hr>

        <h5><?php $opt["lang"]("general"); ?></h5>

        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="first_name">
                    <?php $opt["lang"]("first_name"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input type="text" disabled value="<?php echo htmlspecialchars($opt["first_name"], ENT_QUOTES); ?>" id="first_name">
            </div>
            <div class="medium-6 small-12 columns">
                <label for="last_name">
                    <?php $opt["lang"]("last_name"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input type="text" disabled value="<?php echo htmlspecialchars($opt["name"], ENT_QUOTES); ?>" id="last_name">
            </div>
        </div>

        <div class="row columns">
            <label for="input-position">
                <?php $opt["lang"]("position"); ?>
                <span style="color: red;">*</span>
            </label>
            <input type="text" id="input-position" name="position" value="<?php echo htmlspecialchars($opt["personal_information"]["position"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-birthdate">
                <?php $opt["lang"]("date_of_birth"); ?>
                <span style="color: red;">*</span>
            </label>
            <input type="date" id="input-birthdate" name="birthdate" value="<?php echo $opt["personal_information"]["birthdate"]; ?>">
        </div>

        <div class="row columns">
            <label for="input-nationality">
                <?php $opt["lang"]("nationality"); ?>
                <span style="color: red;">*</span>
            </label>
            <input type="text" id="input-nationality" name="nationality" value="<?php echo htmlspecialchars($opt["personal_information"]["nationality"], ENT_QUOTES); ?>">
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
                            echo "<option ".($country["name"] == $opt["personal_information"]["addr_country"] ? "selected" : "").">$country[name]</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-state">
                    <?php $opt["lang"]("address_state"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-state" type="text" name="addr_state" value="<?php echo htmlspecialchars($opt["personal_information"]["addr_state"], ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="input-addr-city">
                    <?php $opt["lang"]("city"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-city" type="text" name="addr_city" value="<?php echo htmlspecialchars($opt["personal_information"]["addr_city"], ENT_QUOTES); ?>">
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-zip">
                    <?php $opt["lang"]("address_zip"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-zip" type="text" name="addr_zip" value="<?php echo htmlspecialchars($opt["personal_information"]["addr_zip"], ENT_QUOTES); ?>">
            </div>
        </div>
        <div class="row">
            <div class="medium-6 small-12 columns">
                <label for="input-addr-street">
                    <?php $opt["lang"]("street"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-street" type="text" name="addr_street" value="<?php echo htmlspecialchars($opt["personal_information"]["addr_street"], ENT_QUOTES); ?>">
            </div>
            <div class="medium-6 small-12 columns">
                <label for="input-addr-street-number">
                    <?php $opt["lang"]("street_number"); ?>
                    <span style="color: red;">*</span>
                </label>
                <input id="input-addr-street-number" type="text" name="addr_street_number" value="<?php echo htmlspecialchars($opt["personal_information"]["addr_street_number"], ENT_QUOTES); ?>">
            </div>
        </div>
        <a class="button" id="button-autofill-address" onclick="">
            <?php $opt["lang"]("autofill_with_company_data"); ?>
        </a>

        <hr>
        <h5><?php $opt["lang"]("contact_information"); ?></h5>

        <div class="row columns">
            <label for="input-email">
                <?php $opt["lang"]("email"); ?>
                <span style="color: red;">*</span>
            </label>
            <input id="input-email" type="email" name="email" value="<?php echo htmlspecialchars($opt["personal_information"]["email"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-web">
                <?php $opt["lang"]("web"); ?>
            </label>
            <input id="input-web" type="url" name="web" value="<?php echo htmlspecialchars($opt["personal_information"]["web"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-tel">
                <?php $opt["lang"]("phone"); ?>
            </label>
            <input id="input-tel" type="text" name="tel" value="<?php echo htmlspecialchars($opt["personal_information"]["tel"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-mobil">
                <?php $opt["lang"]("mobil"); ?>
            </label>
            <input id="input-mobil" type="text" name="mobil" value="<?php echo htmlspecialchars($opt["personal_information"]["mobil"], ENT_QUOTES); ?>">
        </div>

        <div class="row columns">
            <label for="input-fax">
                <?php $opt["lang"]("fax"); ?>
            </label>
            <input id="input-fax" type="text" name="fax" value="<?php echo htmlspecialchars($opt["personal_information"]["fax"], ENT_QUOTES); ?>">
        </div>

        <a class="button" id="button-autofill-contact">
            <?php $opt["lang"]("autofill_with_company_data"); ?>
        </a>

        <hr>
        <h5><?php $opt["lang"]("professional_history"); ?></h5>
        <div>
            <div id="table-history"></div>
            <button type="button" class="button round" id="button-add-history">
                <span class="show-for-sr">Add</span>
                <span aria-hidden="true">+</span>
            </button>
        </div>

        <hr>
        <h5><?php $opt["lang"]("education"); ?></h5>
        <div>
            <div id="table-education" class="medium-12"></div>
            <button type="button" class="button round" id="button-add-education">
                <span class="show-for-sr"><?php $opt["lang"]("add"); ?></span>
                <span aria-hidden="true">+</span>
            </button>
        </div>

        <hr>
        <button id="button-submit" type="button" class="button" name="Save" value="Save" disabled><?php $opt["lang"]("save"); ?></button>
        <a class="button" onclick="undoRemove()" style="display:none;" id="button-undo-remove"><?php $opt["lang"]("undo_remove"); ?></a>

        <div id="undo-hint-bar" class="undo-hint-bar callout warning" style="display: none;" data-closable>
            <a class="button" onclick="undoRemove()" data-close><?php $opt["lang"]("undo_remove"); ?> <span id="undo-hint-bar-countdown"></span></a>
            <button class="close-button" type="button" data-close>
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

    </form>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "personal_information" => "Persönliche Angaben",
            "general" => "Generell",
            "first_name" => "Vorname",
            "last_name" => "Nachname",
            "position" => "Position",
            "date_of_birth" => "Geburtstag",
            "nationality" => "Nationalität",
            "address" => "Adresse",
            "country" => "Land",
            "address_state" => "Bundesland / Staat",
            "city" => "Stadt",
            "address_zip" => "PLZ (Postleitzahl)",
            "street" => "Straße",
            "street_number" => "Hausnummer",
            "contact_information" => "Kontaktinformationen",
            "email" => "E-Mail-Adresse",
            "web" => "Webseite",
            "phone" => "Telefonnummer",
            "mobil" => "Mobilnummer",
            "fax" => "Faxnummer",
            "professional_history" => " Berufserfahrung",
            "history.start" => "Beginn",
            "history.end" => "Ende",
            "history.title" => "Title",
            "history.delete" => "Entfernen",
            "history.position" => "Position",
            "education" => "Bildung",
            "graduation.end" => "Abschluss",
            "graduation.title" => "Titel",
            "graduation.description" => "Beschreibung",
            "graduation.delete" => "Entfernen",
            "add" => "Hinzufügen",
            "save" => "Speichern",
            "language" => "Sprache",
            "please_choose" => "Bitte auswählen",
            "autofill_with_company_data" => "Mit Firmendaten füllen",
            "scroll_to_bottom" => "Nach unten",
            "feedback_saved" => "Änderungen gespeichert",
            "feedback_unsaved" => "Es gibt ungespeicherte Änderungen",
            "response_not_parsed" => "Die Antwort des Servers konnte nicht verarbeitet werden. Bitte kontaktieren Sie einen Administator!",
            "not_saved" => "Ihre Daten konnten nicht gespeichert werden.",
            "confirm_switch" => "Nicht alle Daten sind gespeichert. Wollen sie fortfahren?",
            "undo_remove" => "Löschen rückgängig machen"
        ],
        "en" => [
            "personal_information" => "Personal information",
            "general" => "General",
            "first_name" => "First name",
            "last_name" => "Last name",
            "position" => "Position",
            "date_of_birth" => "Date of Birth",
            "nationality" => "Nationality",
            "address" => "Address",
            "country" => "Country",
            "address_state" => "State",
            "city" => "City",
            "address_zip" => "ZIP (Postal Code)",
            "street" => "Street",
            "street_number" => "Street number",
            "contact_information" => "Contact information",
            "email" => "Email",
            "web" => "Web",
            "phone" => "Phone",
            "mobil" => "Mobil",
            "fax" => "Fax",
            "professional_history" => "Professional history",
            "history.start" => "Start",
            "history.end" => "End",
            "history.title" => "Title",
            "history.delete" => "Delete",
            "history.position" => "Position",
            "education" => "Education",
            "graduation.start" => "Start",
            "graduation.end" => "Graduation",
            "graduation.title" => "Title",
            "graduation.description" => "Description",
            "graduation.delete" => "Delete",
            "add" => "Add",
            "save" => "Save",
            "language" => "Language",
            "please_choose" => "Please choose",
            "autofill_with_company_data" => "Fill with company data",
            "scroll_to_bottom" => "Take me to the bottom of the page",
            "feedback_saved" => "Saved changes",
            "feedback_unsaved" => "There are unsaved changes",
            "response_not_parsed" => "The response of the server could not be parsed. Please contact an administator.",
            "not_saved" => "There was an error saving your data.",
            "confirm_switch" => "You have unsaved changes. Are you sure you want to switch to another language?",
            "undo_remove" => "Undo Remove"
        ]
    ];
}
?>