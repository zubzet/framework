<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_number",
            type: "text",
        });

        form.createField({
            name: "field_number_required",
            type: "number",
        });

        form.createField({
            name: "field_number_range",
            type: "number",
        });

        form.createField({
            name: "field_number_unique",
            type: "number",
        });

    </script>
<?php }]; ?>