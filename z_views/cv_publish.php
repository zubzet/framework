<?php function head($opt) { ?> <!-- File header -->

<?php } function body($opt) { ?> <!-- File body -->	

    <p><?php $opt["lang"]("info") ?></p>

    <div class="row">
        <div class="small-12">
            <label for="input-comment"><?php $opt["lang"]("comment"); ?></label>
            <input id="input-comment" type="text" name="comment">
            <label for="input-language"><?php $opt["lang"]("language"); ?></label>
            <select id="input-language" name="language_id">
                    <?php 
                        foreach($opt["languages"] as $language) {
                            echo '<option value="'.$language["id"].'">'.$language["nativeName"].'</option>';
                        }
                    ?>
                </select>
        </div>
        <button class="button" id="button-create"><?php $opt["lang"]("generate"); ?></button>
    </div>
    <script>
        document.getElementById("button-create").addEventListener("click", () => {
            var comment = document.getElementById("input-comment").value;
            var language_id = document.getElementById("input-language").value;
            $.post("publish/generate", { 
                comment: comment, 
                language_id: language_id
            }, () => {
                setTimeout(() => {
                    window.location.reload();
                }, 200);
            });
        });
    </script>

    <div id="link-list">
        <?php foreach($opt["list"] as $i => $item) { 
            $link = $opt["base_url"] . $item["ref"];
            ?>
            <hr>
            <div class="row"<?php echo (strtotime($item["created"]) >= time() - 10 ? 'style="background-color: #fff5a8;"' : ''); ?>>
                <div class="link-list-comment small-12">
                    <?php echo (strtotime($item["created"]) >= time() - 10 ? '<b>&gt;&gt; '.$opt["lang"]("new", false).' &lt;&lt;</b><br>' : ''); ?>
                    <b><?php $opt["lang"]("views") ?>: </b><?php echo $item["views"] ?><br>
                    <b><?php $opt["lang"]("from") ?>: </b><?php echo $item["created"] ?><br>
                    <b><?php $opt["lang"]("comment") ?>: </b><?php echo $item["comment"]; ?><br>
                    <b><?php $opt["lang"]("language") ?>: </b><?php echo $opt["languages"][$item["languageId"]]["name"]; ?>
                </div>
                <div class="input-group small-12 medium-10">
                    <span class="input-group-label">
                        <i class="fa fa-link"></i>
                    </span>
                    <input class="input-group-field" id="input-ref-<?php echo $i; ?>" type="text" value="<?php echo $link ?>" disabled>
                </div>
                <button class="button small-6 medium-1 column" id="btn-copy-<?php echo $i; ?>"><?php $opt["lang"]("copy"); ?></button>
                <button class="button small-6 medium-1 column" id="btn-delete-<?php echo $i; ?>"><?php $opt["lang"]("remove"); ?></button>
            </div>
            <script>
                (function() {
                    let i = <?php echo $i; ?>;
                    document.getElementById("btn-copy-" + i).addEventListener("click", () => {
                        let input = document.getElementById("input-ref-" + i);
                        input.disabled = false;
                        input.focus();
                        input.setSelectionRange(0, input.value.length);
                        document.execCommand('copy');
                        input.disabled = true;
                    });

                    let id = <?php echo $item["id"]; ?>;
                    document.getElementById("btn-delete-" + i).addEventListener("click", () => {
                        $.post('publish/delete', { id });
                        setTimeout(() => {
                            window.location.reload();
                        }, 200);
                    });
                })();
            </script>
        <?php } ?>
    </div>

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "generate" => "Einen neuen Link genererieren",
            "comment" => "Kommentar",
            "remove" => "Entfernen",
            "copy" => "Kopieren",
            "views" => "Aufrufe",
            "from" => "Vom",
            "info" => "",
            "language" => "Sprache",
            "new" => "Ihr neuer Eintrag"
        ], 
        "en" => [
            "generate" => "Generate a new link",
            "comment" => "Comment",
            "remove" => "Remove",
            "copy" => "Copy",
            "views" => "Views",
            "from" => "From",
            "info" => "",
            "language" => "Language",
            "new" => "Your new entry"
        ]
    ];
}
?>