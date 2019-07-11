<?php function head($opt) { ?> <!-- File header -->

    <script src="<?php echo $opt["root"]; ?>assets/js/autocomplete.js"></script>
    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/autocomplete.css">

    <style>
        .reference-collapsed-title {
            margin: 10px;
            font-size: 120%;
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

<script>
    const skillCategories = {};
    const availableSkills = {};
    const skillById = [];

    const refList = [];

    const deleteLog = [];

    class Reference {

        constructor(dbId, title, desc, descShort, position, client, start, end, skill) {
            this.dbId = dbId;
            this.removed = false;
            this.skillValue = skill;

            this.dom = $(`<div class="callout secondary"></div>`);
            this.collapseLine = $(`<div class="row columns">`);
            this.collapseLine.hide();
            this.dom.append(this.collapseLine);
            this.content = $("<div class='row'>");
            this.dom.append(this.content);

            this.buttonCollapse = $(`<div class="columns"><a class="button">⯆</a></div>`);
            this.buttonCollapse.click(() => this.collapse());

            this.content.append(this.buttonCollapse);

            this.buttonUncollapse = $(`<a class="button" style="margin-bottom: 0px">⯈</a>`);
            this.buttonUncollapse.click(() => this.uncollapse());
            this.collapseLine.append(this.buttonUncollapse);

            this.collapseText = $(`<span class="reference-collapsed-title">`);
            this.collapseLine.append(this.collapseText);
            this.collapseDate = $(`<span class="reference-collapsed-date float-right">`);
            this.collapseLine.append(this.collapseDate);

            this.inputTitle = $("<input type='text' required>").val(title);
            this.inputTitle.on('change', () => hintUnsaved());
            var blockTitle = $("<div class='columns medium-12 small-12'><label><?php $opt["lang"]("title"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputTitle);

            this.inputDescription = $("<textarea required rows='3'>").val(desc).css({"resize": "vertical"});
            this.inputDescription.on('change', () => hintUnsaved());
            var blockDescription = $("<div class='columns medium-12 small-12'><label><?php $opt["lang"]("description"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputDescription);

            this.inputDescriptionShort = $("<input type='text' required>").val(descShort);
            this.inputDescriptionShort.on('change', () => hintUnsaved());
            var blockDescriptionShort = $("<div class='columns medium-12 small-12'><label><?php $opt["lang"]("short_description"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputDescriptionShort);

            this.inputPosition = $("<input type='text' required>").val(position);
            this.inputPosition.on('change', () => hintUnsaved());
            var blockPosition = $("<div class='columns medium-12 small-12'><label><?php $opt["lang"]("position"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputPosition);

            this.inputClient = $("<input type='text'>").val(client);
            this.inputClient.on('change', () => hintUnsaved());
            var blockClient = $("<div class='columns medium-12 small-12'><label><?php $opt["lang"]("client"); ?></label></div>").append(this.inputClient);

            this.inputStart = $("<input type='date'>").val(start);
            this.inputStart.on('change', () => hintUnsaved());
            var blockStart = $("<div class='columns medium-6 small-12'><label><?php $opt["lang"]("start"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputStart);

            this.inputEnd = $("<input type='date'>").val(end);
            this.inputEnd.on('change', () => hintUnsaved());
            var blockEnd = $("<div class='columns medium-6 small-12'><label><?php $opt["lang"]("end"); ?></label></div>").append(this.inputEnd);

            this.inputSkill = $("<input type='text' class='autocomplete'>");
            if (skill != -1) {
                if (skillById[skill]) {
                    this.inputSkill.val(skillById[skill].text);
                }
            }
            var blockSkill = $("<div class='columns medium-6 small-12'><label><?php $opt["lang"]("skill"); ?> <span style=\"color: red;\">*</span></label></div>").append(this.inputSkill);
            
            this.inputSkillCategory = $("<select required></select>");
            var blockSkillCategory = $("<div class='columns medium-6 small-12'><label><?php $opt["lang"]("skill_category"); ?></label></div>").append(this.inputSkillCategory);
            this.inputSkillCategory.on('change', () => {
                this.categoryChanged(this.inputSkillCategory.val());
            });

            this.inputSkillCategory.append("<option value='-1'>All</option>")
            for (var k in skillCategories) {
                this.inputSkillCategory.append(`<option value="${k}">${skillCategories[k]}</option>`);
            }

            var buttonRemove = $("<a class='button'><?php $opt["lang"]("remove"); ?></a>");
            buttonRemove.click(this.remove.bind(this));
            var blockRemove = $("<div class='columns medium-12 small-12'></div>").append(buttonRemove);

            this.content.append(blockTitle, blockDescription, blockDescriptionShort, blockPosition, blockClient, blockStart, blockEnd, blockSkill, blockSkillCategory, blockRemove);

            this.categoryChanged(-1);
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
                return `&references[${index}][change]=remove&references[${index}][id]=${this.dbId}`;
            }
            var text;
            if (this.dbId == -1) {
                text = `&references[${index}][change]=add`;
            } else {
                text = `&references[${index}][change]=edit`;
            }

            text += `&references[${index}][id]=${this.dbId}`
                +`&references[${index}][start]=${this.inputStart.val()}`
                +`&references[${index}][end]=${this.inputEnd.val()}`
                +`&references[${index}][title]=<#decURI#>${encodeURIComponent(this.inputTitle.val())}`
                +`&references[${index}][description]=<#decURI#>${encodeURIComponent(this.inputDescription.val())}`
                +`&references[${index}][client]=<#decURI#>${encodeURIComponent(this.inputClient.val())}`
                +`&references[${index}][short_description]=<#decURI#>${encodeURIComponent(this.inputDescriptionShort.val())}`
                +`&references[${index}][position]=<#decURI#>${encodeURIComponent(this.inputPosition.val())}`
                +`&references[${index}][skillId]=${this.skillValue}`;
            return text;
        }

        categoryChanged(id) {
            var skills;
            if (id == -1) {
                skills = [];
                for (var k in availableSkills) {
                    skills = skills.concat(availableSkills[k]);
                }
            } else {
                skills = availableSkills[id];
            }

            if (skills == undefined) skills = [];
            autocomplete(this.inputSkill[0], skills, (dat) => {
                console.log(dat);
                this.skillValue = dat.id;
                hintUnsaved();
            });
        }

        collapse() {
            this.content.slideUp();
            this.collapseLine.slideDown();
            this.collapseText.text(this.inputTitle.val());

            var text = "";
            if (this.inputStart.val()) text +=  new Date(this.inputStart.val()).toLocaleDateString();
            if (this.inputStart.val() && this.inputEnd.val()) text += " - ";
            if (this.inputEnd.val()) text +=  new Date(this.inputEnd.val()).toLocaleDateString();
            this.collapseDate.text(text);
        }

        collapseFast() {
            this.content.hide();
            this.collapseLine.show();
            this.collapseText.text(this.inputTitle.val());
            
            var text = "";
            if (this.inputStart.val()) text +=  new Date(this.inputStart.val()).toLocaleDateString();
            if (this.inputStart.val() && this.inputEnd.val()) text += " - ";
            if (this.inputEnd.val()) text +=  new Date(this.inputEnd.val()).toLocaleDateString();
            this.collapseDate.text(text);
        }

        uncollapse() {
            this.content.slideDown();
            this.collapseLine.slideUp();
        }
    }

    function addReference(dbId = -1, title = "", desc = "", descShort = "", position = "", client = "", start = "", end = "", skill = 0) {
        var ref = new Reference(dbId, title, desc, descShort, position, client, start, end, skill);
        $("#reference-list").append(ref.dom);
        refList.push(ref);
    }

    $(()=>{
        $("#button-add-reference").click(()=>{ addReference(); hintUnsaved(); });

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
                window.location.replace("<?php echo $opt["root"]; ?>cv/references/" + value); //Hack (User relative path)
            } else {
                $("#select-language").val(lastLanguage);
            }
        });

        <?php 
            foreach($opt["references"] as $row) {
                echo ('addReference('.$row["id"].',`'.addcslashes($row["title"], "`").'`,`'.addcslashes($row["description"], "`").'`,`'.addcslashes($row["short_description"], "`").'`,`'.addcslashes($row["position"], "`").'`,`'.addcslashes($row["client"], "`").'`,`'.addcslashes($row["start"], "`").'`,`'.addcslashes($row["end"], "`").'`,`'.$row["skillId"].'`);');
            }
        ?>
        for (var ref of refList) ref.collapseFast();

        $("#button-save").click(()=>{
            var data = "Save=1";
            for (var i = 0; i < refList.length; i++) {
                data += refList[i].getPostString(i);
            }
            console.log(data);

            if (!$("#form")[0].checkValidity()) {
                alert('<?php $opt["lang"]("required_fields"); ?>');
                return;
            }

            $.post("", data, (res)=>{
                try {
                    obj = JSON.parse(res);
                    if (obj.result == "success") {
                        hintSaved();
                    } else {
                        alert('<?php $opt["lang"]("not_saved"); ?>');
                    }
                } catch (e) {
                    alert('<?php $opt["lang"]("response_not_parsed"); ?>');
                }
            });
        });

        $("#filter-title, #filter-date-from, #filter-skill, #filter-date-to").on('keyup change', e => {
            applySortAndFilter();
        });

        $("#sort-title").click(e => {
            sortDate = null;
            $("#sort-date").text("▼▲");

            if (sortTitle == null) {
                sortTitle = true;
            } else {
                sortTitle = !sortTitle;
            }
            $("#sort-title").text(sortTitle ? "▼" : "▲");
            applySortAndFilter();
        });

        $("#sort-date").click(e => {
            sortTitle = null;
            $("#sort-title").text("▼▲");

            if (sortDate == null) {
                sortDate = true;
            } else {
                sortDate = !sortDate;
            }
            $("#sort-date").text(sortDate ? "▼" : "▲");
            applySortAndFilter();
        });

    });

    var sortTitle = null;
    var sortDate = null;
    function applySortAndFilter() {
        var filterTitle = $("#filter-title").val().toLowerCase();
        var filterDateFrom = $("#filter-date-from").val();
        var filterDateTo = $("#filter-date-to").val();

        var sortedList = refList;

        if (filterTitle) {
            sortedList = sortedList.filter(a => a.inputTitle.val().toLowerCase().includes(filterTitle));
        }

        if (filterDateFrom || filterDateTo) {
            if (!filterDateFrom) filterDateFrom = "0000-01-01";
            if (!filterDateTo) filterDateTo = "9999-12-30"; //ToDo: change this in 7980 years

            sortedList = sortedList.filter(a => {
                var start = a.inputStart.val() || "0000-01-01";
                var end = a.inputEnd.val() || "9999-12-29"; //ToDo: change this too, future me

                return (filterDateFrom < end && filterDateTo > start);
            });
        }

        if (filterSkill) {
            //ToDo: check when skill tree exists
        }

        if (sortTitle !== null) {
            sortedList = sortedList.sort((a, b) => a.inputTitle.val().localeCompare(b.inputTitle.val()));
            if (!sortTitle) sortedList = sortedList.reverse();
        }

        if (sortDate !== null) {
            sortedList = sortedList.sort((a, b) => (a.inputStart.val() || a.inputEnd.val()).localeCompare((b.inputStart.val() || b.inputEnd.val())));
            if (!sortDate) sortedList = sortedList.reverse();
        }

        for (var ref of refList) {
            ref.dom.detach();
        }
        for (var ref of sortedList) {
            $("#reference-list").append(ref.dom);
        }
    }

    function addSkillToAviables(id, name, category, occ) {
        var skill = {text: name, id: id, category: category, occ: occ};
        if (!availableSkills[category]) availableSkills[category] = [];
        availableSkills[category].push(skill);
        skillById[id] = skill;
    }

    function addSkillCategory(id, name) {
        skillCategories[id] = name;
    }

    <?php 
        foreach($opt["skill_list"] as $row) {
            echo ("addSkillToAviables(".$row["id"].",'".addcslashes($row["name"], "'")."','".$row["categoryId"]."',".$row["OCC"].");");
        }
        foreach($opt["skill_categories"] as $row) {
            echo ("addSkillCategory(".$row["id"].",'".addcslashes($row["name"], "'")."');");
        }
    ?>

    unsaved = false;
    function hintUnsaved() {
        $("#button-save").attr("disabled", false);
        $("#save-feedback").slideUp();
        $("#unsaved-feedback").fadeIn();
        $("#unsaved-spacer").slideDown();
        unsaved = true;
    }

    function hintSaved() {
        $("#button-save").attr("disabled", true);
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

    <h1><?php $opt["lang"]("references"); ?></h1>

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

    <a class="button" onclick="window.scrollTo(0, document.body.scrollHeight);"><?php $opt["lang"]("scroll_to_bottom"); ?></a>
    <a class="button" onclick="undoRemove()" style="display:none;" id="button-undo-remove"><?php $opt["lang"]("undo_remove"); ?></a>

    <h5><?php $opt["lang"]("filter"); ?></h5>
    <div class="row">
        <div class="medium-3 small-12 columns">
            <label for=""><?php $opt["lang"]("title"); ?></label>
            <div class="input-group">
                <input class="input-group-field" type="text" id="filter-title">
                <div class="input-group-button">
                    <button class="button" id="sort-title">▲▼</button>
                </div>
            </div>
        </div>
        <div class="medium-3 small-12 columns">
            <label for=""><?php $opt["lang"]("skill"); ?></label>
            <input type="text" id="filter-skill">
        </div>
        <div class="medium-6 small-12 columns">
            <label for=""><?php $opt["lang"]("start"); ?></label>
            <div class="input-group">
                <span class="input-group-label"><?php $opt["lang"]("from"); ?></span>
                <input class="input-group-field" type="date" id="filter-date-from">
                <span class="input-group-label"><?php $opt["lang"]("to"); ?></span>
                <input class="input-group-field" type="date" id="filter-date-to">
                <div class="input-group-button">
                    <button class="button" id="sort-date">▲▼</button>
                </div>
            </div>
        </div>
    </div>

    <form id="form">
        <div id="reference-list">

        </div>
    </form>

    <button id="button-save" class="button" disabled><?php $opt["lang"]("save"); ?></button>

    <button type="button" class="button round" id="button-add-reference">
        <span class="show-for-sr"><?php $opt["lang"]("add"); ?></span>
        <span aria-hidden="true">+</span>
    </button>

    <div id="undo-hint-bar" class="undo-hint-bar callout warning" style="display: none;" data-closable>
        <a class="button" onclick="undoRemove()" data-close><?php $opt["lang"]("undo_remove"); ?> <span id="undo-hint-bar-countdown"></span></a>
        <button class="close-button" type="button" data-close>
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "references" => "Referenzen",
                "add" => "Hinzufügen",
                "save" => "Speichern",
                "title" => "Titel",
                "description" => "Description",
                "short_description" => "Kurzbeschreibung",
                "position" => "Position",
                "client" => "Kunde",
                "start" => "Beginn",
                "end" => "Abschluss",
                "skill" => "Fähigkeit",
                "skill_category" => "Fähigkeitskategorie",
                "remove" => "Löschen",
                "language" => "Sprache",
                "please_choose" => "Bitte auswählen",
                "scroll_to_bottom" => "Nach unten",
                "feedback_saved" => "Änderungen gespeichert",
                "feedback_unsaved" => "Es gibt ungespeicherte Änderungen",
                "required_fields" => "Bitte füllen Sie alle erforderlichen Felder aus!",
                "response_not_parsed" => "Die Antwort des Servers konnte nicht verarbeitet werden. Bitte kontaktieren Sie einen Administator!",
                "not_saved" => "Ihre Daten konnten nicht gespeichert werden.",
                "confirm_switch" => "Nicht alle Daten sind gespeichert. Wollen sie fortfahren?",
                "undo_remove" => "Löschen rückgängig machen",
                "filter" => "Filter / Sortieren",
                "from" => "Ab",
                "to" => "Bis"
            ],
            "en" => [
                "references" => "References",
                "add" => "Add",
                "save" => "Save",
                "title" => "Title",
                "description" => "Description",
                "short_description" => "Short description",
                "position" => "Position",
                "client" => "Client",
                "start" => "Start",
                "end" => "End",
                "skill" => "Skill",
                "skill_category" => "Skill category",
                "remove" => "Remove",
                "language" => "Language",
                "please_choose" => "Please choose",
                "scroll_to_bottom" => "Take me to the bottom of the page",
                "feedback_saved" => "Saved changes",
                "feedback_unsaved" => "There are unsaved changes",
                "required_fields" => "Please fill in all required fields",
                "response_not_parsed" => "The response of the server could not be parsed. Please contact an administator.",
                "not_saved" => "There was an error saving your data.",
                "confirm_switch" => "You have unsaved changes. Are you sure you want to switch to another language?",
                "undo_remove" => "Undo Remove",
                "filter" => "Filter / Sort",
                "from" => "From",
                "to" => "To"
            ]
        ];
    }
?>