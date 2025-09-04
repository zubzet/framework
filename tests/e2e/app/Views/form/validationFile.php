<?php return [ 'body' => function($opt) { ?>
    <div>
        <?php foreach($opt["files"] as $file) {?>
            <li><?= $file["name"] ?></li>
         <?php } ?>
    </div>

    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "file",
            type: "file",
        });

    </script>
<?php }]; ?>