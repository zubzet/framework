<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_url",
            type: "url",
        });

        form.createField({
            name: "field_url_required",
            type: "url",
        });

        form.createField({
            name: "field_url_length",
            type: "url",
        });

        form.createField({
            name: "field_url_unique",
            type: "url",
        });

    </script>
<?php }]; ?>