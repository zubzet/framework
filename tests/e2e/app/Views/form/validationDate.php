<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_date",
            type: "text",
        });

        form.createField({
            name: "field_date_required",
            type: "date",
        });

        form.createField({
            name: "field_date_length",
            type: "date",
        });

        form.createField({
            name: "field_date_unique",
            type: "date",
        });

    </script>
<?php }]; ?>