<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <div class="mt-4">
        <ul>
            <?php foreach($opt["genders"] as $gender) {?>
                <li><?= e($gender["name"]) ?></li>
            <?php } ?>
        </ul>
    </div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "name",
            type: "text",
            text: "Geschlecht",
            width: 12,
            required: true,
        });

        form.saveHook = (res) => {
            location.reload();
        };

    </script>
<?php }]; ?>