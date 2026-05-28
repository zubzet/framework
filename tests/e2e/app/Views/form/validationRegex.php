<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_regex",
            type: "text",
        });

        form.createField({
            name: "field_regex_exceptions",
            type: "text",
        });

    </script>
<?php }]; ?>
