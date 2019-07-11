<?php function head($opt) { ?> <!-- File header -->

    <script>

        class Skill {

            constructor(dbId, rowId, category, name, usage) {
                this.dbId = dbId;
                this.rowId = rowId;
                this.category = category;
                this.name = name;
                this.usage = usage;

                this.dom = $(`<div class="row"></div>`);

                var blockName = $(`<div class="medium-5 small-12 columns"></div>`);
                this.inputName = $(`<input type="text">`);
                blockName.append(this.inputName);
                this.inputName.on('change', () => this.addToEdited());

                var blockCategory = $(`<div class="medium-5 small-12 columns"></div>`);
                this.inputCategory = $(`<select></select>`);
                blockCategory.append(this.inputCategory);

                for (var i = 0; i < categoryList.length; i++) {
                    var cat = categoryList[i];
                    this.inputCategory.append(`<option value="${cat.id}">${cat.name}</option>`);
                }
                this.inputCategory.val(category);
                this.inputCategory.on('change', () => this.addToEdited());

                var blockDestroy = $(`<div class="medium-2 small-12 columns"></div>`);
                var buttonDestroy = $(`<button class="button"><?php $opt["lang"]("remove"); ?></button>`);
                blockDestroy.append(buttonDestroy);

                this.dom.append(blockName);
                this.dom.append(blockCategory);
                this.dom.append(blockDestroy);

                this.dom.append("<hr>");

                this.inputName.val(name);
                buttonDestroy.click(this.delete.bind(this));
            }

            delete() {
                if (this.usage != 0) {
                    if (!confirm("This skill is used by "+this.usage+" employee(s). Do you really want to delete it?")) {
                        return;
                    }
                }

                this.dom.remove();
                skillList.splice(skillList.indexOf(this), 1);
                if (this.dbId != -1) {
                    deleteLog.push({id: this.dbId});
                }
            }

            addToEdited() {
                if (!editLog.includes(this.dbId)) {
                    editLog.push(this.dbId);
                }
            }

        }

        class Category {

            constructor(id, name) {
                this.id = id;
                this.name = name;
            }

        }

        var nextRowId = 0;
        var skillList = [];
        var categoryList = [];
        var deleteLog = [];
        var editLog = [];

        function addSkillToList(dbId, category, name, usage) {
            var skill = new Skill(dbId, nextRowId++, category, name, usage);
            skillList.push(skill);
            $("#skill-table").append(skill.dom);
        }

        function addCategory(id, name) {
            categoryList.push(new Category(id, name));
        }

        function sendData() {
            var str = "Save=1";
            for (var i = 0; i < skillList.length; i++) {
                var skill = skillList[i];
                if (!editLog.includes(skill.dbId) && skill.dbId != -1) continue;

                str += `&skill[${i}][id]=${skill.dbId}`;
                if (skill.dbId == -1) {
                    str += `&skill[${i}][change]=add`;
                } else {
                    str += `&skill[${i}][change]=edit`;
                }
                str += `&skill[${i}][name]=${skill.inputName.val()}`;
                str += `&skill[${i}][categoryId]=${skill.inputCategory.val()}`;
                
            }
            for (j = 0; j < deleteLog.length; j++) {
                var skill = deleteLog[j];
                str += `&skill[${i+j}][id]=${skill.id}`;
                str += `&skill[${i+j}][change]=remove`;
            }

            sendPost("", str, 
                (text) => {
                    var obj = JSON.parse(text);
                    if (obj.result == "success") {
                        alert('<?php $opt["lang"]("data_saved"); ?>');
                        //window.location.reload();
                    } else {
                        $("#button-save").prop('disabled', false);
                        alert('<?php $opt["lang"]("data_save_problem"); ?>');    
                    }
                }, () => {
                    $("#button-save").prop('disabled', false);
                    alert('<?php $opt["lang"]("progress_not_saved"); ?>');    
                });
            }

            function sendPost(Purl, Pparams, cb = null, er = null) {
                var http = new XMLHttpRequest();
                var url = Purl;
                var params = Pparams;
                http.open('POST', url, true);
                http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                http.onreadystatechange = function() {
                    if (http.readyState == 4) {
                        if (http.status == 200) {
                            console.log(http.responseText);
                            if (cb != null) {
                                cb(http.responseText);
                            }
                        } else {
                            if (er != null) {
                                er(http.responseText);
                            }
                        }
                    }
                }
                http.send(params);
            }

        $(function() {
            $("#btn-add-skill").click(()=>{
                addSkillToList(-1, 1, "", 0);
            });

            $("#button-save").click(()=>{
                sendData();
            });

            <?php 
                foreach($opt["categories"] as $row) {
                    echo "addCategory(".$row["id"].",'".addcslashes($row["name"], "'")."');";
                } 
            ?>
            
            <?php /*For the line break*/ ?>
            <?php
                foreach($opt["skills"] as $row) {
                    echo "addSkillToList(".$row["id"].",'".$row["categoryId"]."','".addcslashes($row["name"], "'")."',".$row["OCC"].");";
                }
            ?>

        });
    </script>

<?php } function body($opt) { ?> <!-- File body -->	

    <h1><?php $opt["lang"]("title"); ?></h1>

    <div class="skill-table-wrapper">
        <div class="grid-container">
            <div class="hide-for-small-only row">
                <div class="medium-5 columns">
                    <?php $opt["lang"]("name"); ?>
                    <span style="color: red;">*</span>
                </div>
                <div class="medium-5 columns">
                    <?php $opt["lang"]("category"); ?>
                    <span style="color: red;">*</span>
                </div>
                <div class="medium-2 columns"></div>
            </div>
            <div class="grid-x grid-padding-x" id="skill-table">
                <!-- Skill table content -->
            </div>
        </div>
    </div>

    <button class="button round" id="btn-add-skill">
        <span class="show-for-sr"><?php $opt["lang"]("add"); ?></span>
        <span aria-hidden="true">+</span>
    </button>

    <input id="button-save" type="submit" class="button" name="Save" value="<?php $opt["lang"]("save"); ?>">

<?php } 
    function getLangArray() {
        return [
            "de_formal" => [
                "title" => "Fähigkeiten",
                "name" => "Name",
                "category" => "Kategorie",
                "add" => "Hinzufügen",
                "remove" => "Entfernen",
                "save" => "Speichern",
                "data_save_problem" => "Es hab Problem bei der Speicherung der Daten. Manche könnten nicht gespeichert worden sein.",
                "data_saved" => "Daten gespeichert!",
                "progress_not_saved" => "Ihre Daten konnten nicht gespeichert werden. Bitte kontaktieren Sie einen Administator."
            ],
            "en" => [
                "title" => "Skills",
                "name" => "Name",
                "category" => "Category",
                "add" => "Add",
                "remove" => "Remove",
                "save" => "Save",
                "data_save_problem" => "There were problems saving the data. Some may be lost.",
                "data_saved" => "Data saved!",
                "progress_not_saved" => "Your progress could not be saved! Please contact an administrator."
            ]
        ];
    }
?>