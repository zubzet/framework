<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_text",
            type: "text",
        });

        form.createField({
            name: "field_text_required",
            type: "text",
        });

        form.createField({
            name: "field_text_length",
            type: "text",
        });

        form.createField({
            name: "field_text_unique",
            type: "text",
        });

    </script>
<?php }]; ?>