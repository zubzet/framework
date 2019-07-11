<?php function head($opt) { ?> <!-- File header -->

    <script>
        function confirmSave() {
            if (["I read the warning and want to save", "dev"].includes(prompt(`Please write "I read the warning and want to save" to confirm:`, "")) ) {
                document.getElementById("init_cfg_form").submit();
            }
        }
    </script>

<?php } function body($opt) { ?> <!-- File body -->	

    <form method="post" id="init_cfg_form">

        <input type="hidden" name="Save" value="Save">

        <h1><?php $opt["lang"]("title"); ?></h1>

        <div class="callout alert">
        <h5><b><?php $opt["lang"]("warning_title"); ?></b></h5>
        <p><?php $opt["lang"]("warning_content"); ?></p>
        </div>

        <?php foreach ($opt["configured_fields"] as $opt_name => $opt_val) { ?>
            <div class="row">
                <div class="medium-12 small-12 columns">
                    <label for="<?php echo $opt_name; ?>"><?php echo $opt_name; ?></label>
                    <input id="<?php echo $opt_name; ?>" name="<?php echo $opt_name; ?>" type="text" value="<?php echo $opt_val; ?>">
                </div>
            </div>
        <?php } ?>
                
        <div class="row">
            <div class="medium-12 small-12 columns">
                <input id="button-save" type="button" class="button" onClick="confirmSave();" value='<?php $opt["lang"]("save") ?>'>
            </div>
        </div>

    </form>

<?php }
    function getLangArray() {
        return [
            "de_formal" => [
                "title" => "Instanz",
                "warning_title" => "Achtung.",
                "warning_content" => "Ändern Sie die Einstellungen in diesem Menü nur, wenn Sie die Dokumentation gelesen haben und wissen was Sie tun. Sollten Sie bei der Konfiguration einen Fehler machen, kann diese Instanz möglicherweise, ohne externe Wiederherstellungshilfe, nicht mehr verwendbar sein.",
                "save" => "Speichern"
            ],
            "en" => [
                "title" => "Instance",
                "warning_title" => "Be advised.",
                "warning_content" => "Only change settings in this menu if you know what you are doing and read the documentation. If you make an error configuring this part, this instance may not be useable anymore without any external recovery help.",
                "save" => "Save"
            ]
        ];
    }
?>