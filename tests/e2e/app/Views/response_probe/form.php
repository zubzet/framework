<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "col_a",
            type: "text",
            required: true,
            value: <?= json_encode($opt["row"]["col_a"] ?? "") ?>,
        });

        form.createField({
            name: "col_b",
            type: "number",
            required: true,
            value: <?= json_encode($opt["row"]["col_b"] ?? "") ?>,
        });

        // Optional file field - exercises uploadFromForm's noSave branch
        // when the form is submitted without a file attached.
        form.createField({
            name: "file_id",
            type: "file",
        });
    </script>
<?php }]; ?>
