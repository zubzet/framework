<?php function head($opt) { ?> <!-- File header -->

    <script src="<?php echo $opt["root"]; ?>assets/js/autocomplete.js"></script>
    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/autocomplete.css">

    <script>

        function hintUnsaved() {
            $("#button-save").attr("disabled", false);
            $("#save-feedback").css({display: "none"});
            $("#unsaved-feedback").css({display: ""});
        }

        function hintSaved() {
            $("#button-save").attr("disabled", true);
            $("#save-feedback").css({display: ""});
            $("#unsaved-feedback").css({display: "none"});
        }

        class SkillAssignment {

            constructor(assId, skillId, time, level) {
                this.assId = assId;
                this.skillId = skillId;
                this.time = time;
                this.level = level;

                this.dom = $(`<div class="row"></div>`);

                var inputName = $(`<input type="text" disabled>`);
                inputName.val(skillById[skillId].text);
                var blockName = $(`<div class="medium-4 small-12 columns"><label class="show-for-small-only">Skill Name</label></div>`);
                blockName.append(inputName);

                var blockTime = $(`<div class="medium-2 small-12 columns"><label class="show-for-small-only">Experience [Years]</label></div>`);
                this.inputTime = $(`<input type="number" step="0.1">`);
                this.inputTime.change(() => hintUnsaved());
                blockTime.append(this.inputTime);
                this.inputTime.val(this.time);

                var blockLevel = $(`<div class="medium-5 small-12 columns"><label class="show-for-small-only">Level</label></div>`);
                this.inputLevel = $(`<select></select>`);
                this.inputLevel.change(() => hintUnsaved());
                blockLevel.append(this.inputLevel);

                <?php foreach ($opt["skill_scales"] as $scale) { ?>
                    this.inputLevel.append($(`<option value="<?php echo $scale['id'] ?>"><?php echo $scale['name'] ?></option>`));
                <?php }?>

                this.inputLevel.val(level);

                var blockDestroy = $(`<div class="medium-1 small-12 columns"></div>`);
                var buttonDestroy = $(`<button class="button"><?php $opt["lang"]("remove"); ?></button>`);
                blockDestroy.append(buttonDestroy);

                this.dom.append(blockName);
                this.dom.append(blockTime);
                this.dom.append(blockLevel);
                this.dom.append(blockDestroy);

                this.dom.append("<hr>");
                buttonDestroy.click(this.remove.bind(this));
            }

            remove() {
                if (this.assId != -1) {
                    deleteLog.push(this.assId);
                }
                this.dom.remove();
                hintUnsaved();
            }

            focus() {
                this.inputTime.focus();
            }
        }

        availableSkills = {};
        skillById = [];

        skillList = [];
        deleteLog = [];
        
        $(() => {
            categoryChanged(-1);
            $("#create-category").on("change", (e) => {
                categoryChanged($("#create-category").val());
                $("#create-name").focus();
            });

            $("#button-save").click(() => {

                var str = "Save=1";
                for (var i = 0; i < skillList.length; i++) {
                    var skill = skillList[i];
                    if (skill.assId == -1) {
                        str += `&skill_assignment[${i}][change]=add`;
                        str += `&skill_assignment[${i}][skillId]=${skill.skillId}`;
                    } else {
                        str += `&skill_assignment[${i}][change]=edit`;
                        str += `&skill_assignment[${i}][id]=${skill.assId}`;
                    }
                    str += `&skill_assignment[${i}][scaleId]=${skill.inputLevel.val()}`;
                    str += `&skill_assignment[${i}][experience]=${skill.inputTime.val()}`;
                }
                for (j = 0; j < deleteLog.length; j++) {
                    var skill = deleteLog[j];
                    str += `&skill_assignment[${i+j}][id]=${skill}`;
                    str += `&skill_assignment[${i+j}][change]=remove`;
                }

                console.log(str);

                
                $.ajax({
                    type: "POST",
                    url: "",
                    data: str,
                    success: function(msg) {
                        try {
                            console.log(msg);
                            var obj = JSON.parse(msg);

                            if (obj.result == "success") {
                                deleteLog = [];
                                hintSaved();
                            } else {
                                alert('<?php $opt["lang"]("not_saved"); ?>');
                            }
                        } catch(e) {
                            alert('<?php $opt["lang"]("response_not_parsed"); ?>');
                            console.error(e);
                        }
                    }
                });
            
            });

            <?php
                foreach($opt["skill_assignments"] as $row) {
                    echo ("addSkillToList(".$row["skillAssignmentId"].",'".$row["skillId"]."','".$row["experience"]."',".$row["skillScaleId"].");");
                }
            ?>
            $("#unsaved-feedback").css({display: "none"});
            $("#saved-feedback").css({display: "none"});

        });

        function categoryChanged(id) {
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
            autocomplete(document.getElementById("create-name"), skills, (dat) => {
                hintUnsaved();
                console.log(dat);
                addSkillToList(-1, dat.id, 0, 1);
                document.getElementById("create-name").value = "";
            });
        }

        function addSkillToList(assId, skillId, time, level) {
            for (var i = 0; i < skillList.length; i++) {
                if (skillList[i].skillId == skillId) return;
            }

            var ass = new SkillAssignment(assId, skillId, time, level);
            skillList.push(ass);
            $("#skill-list").append(ass.dom);
            ass.focus();
        }

        function addSkillToAviables(id, name, category, occ) {
            var skill = {text: name, id: id, category: category, occ: occ};
            if (!availableSkills[category]) availableSkills[category] = [];
            availableSkills[category].push(skill);
            skillById[id] = skill;
        }

        <?php 
            foreach($opt["skill_list"] as $row) {
                echo ("addSkillToAviables(".$row["id"].",'".addcslashes($row["name"], "'")."','".$row["categoryId"]."',".$row["OCC"].");");
            }
        ?>
    </script>

<?php } function body($opt) { ?> <!-- File body -->	
    <h1><?php $opt["lang"]("skills"); ?></h1>

    <div id="save-feedback" class="callout success" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_saved"); ?></span>
    </div>

    <div id="unsaved-feedback" class="callout warning" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_unsaved"); ?></span>
    </div>

    <h5><?php $opt["lang"]("add_skill"); ?></h5>
    <div class="row">
        <div class="medium-6 columns">
            <label for="create-name"><?php $opt["lang"]("skill_name"); ?></label>
            <input type="text" id="create-name" class="autocomplete" autofocus >
        </div>
        <div class="medium-6 columns">
            <label for="create-category"><?php $opt["lang"]("category"); ?></label>
            <select id="create-category">
                <option value="-1"><?php $opt["lang"]("all"); ?></option>
                <?php 
                    foreach($opt["skill_categories"] as $row) {
                        echo "<option value='".$row["id"]."'>".$row["name"]."</option>";
                    }
                ?>
            </select>
        </div>
    </div>

    <hr>

    <h5><?php $opt["lang"]("your_skills"); ?></h5>
    <div class="hide-for-small-only row">
        <div class="medium-4 columns"><?php $opt["lang"]("skill_name"); ?></div>
        <div class="medium-2 columns">
            <?php $opt["lang"]("experience"); ?>
            <span style="color: red;">*</span>
        </div>
        <div class="medium-5 columns">
            <?php $opt["lang"]("scale"); ?>
            <span style="color: red;">*</span>
        </div>
    </div>
    <div id="skill-list"></div>

    <input id="button-save" type="submit" disabled class="button" name="Save" value="<?php $opt["lang"]("save"); ?>">
<?php } function getLangArray() {
    return [
        "de_formal" => [
            "skills" => "Angaben zu Ihren Fähigkeiten",
            "add_skill" => "Fähigkeit hinzufügen",
            "skill_name" => "Fähigkeitsname",
            "category" => "Kategorie",
            "your_skills" => "Deine Fähigkeiten",
            "experience" => "Erfahrung [Jahre]",
            "scale" => "Stufe",
            "save" => "Speichern",
            "remove" => "Entfernen",
            "all" => "Alle",
            "feedback_saved" => "Gepseichert!",
            "feedback_unsaved" => "Änderungen sind noch nicht gespeichert",
            "response_not_parsed" => "Die Antwort des Servers konnte nicht verarbeitet werden. Bitte kontaktieren Sie einen Administator!",
            "not_saved" => "Ihre Daten konnten nicht gespeichert werden."
        ], 
        "en" => [
            "skills" => "Skills",
            "add_skill" => "Add Skill",
            "skill_name" => "Skill Name",
            "category" => "Category",
            "your_skills" => "Your Skills",
            "experience" => "Experience [Years]",
            "scale" => "Scale",
            "save" => "Save",
            "remove" => "Remove",
            "all" => "All",
            "feedback_saved" => "Saved!",
            "feedback_unsaved" => "There are unsaved changes",
            "response_not_parsed" => "The response of the server could not be parsed. Please contact an administator.",
            "not_saved" => "There was an error saving your data."
        ]
    ];
}
?>