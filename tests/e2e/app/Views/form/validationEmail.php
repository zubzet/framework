<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_email",
            type: "email",
        });

        form.createField({
            name: "field_email_required",
            type: "email",
        });

        form.createField({
            name: "field_email_length",
            type: "email",
        });

        form.createField({
            name: "field_email_unique",
            type: "email",
        });

    </script>
<?php }]; ?>