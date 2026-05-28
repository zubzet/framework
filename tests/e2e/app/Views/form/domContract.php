<?php return [ "body" => function($opt) { ?>
    <div id="form" data-test="form"></div>

    <script>
        var form = Z.Forms.create({ dom: "form" });

        form.createField({ name: "c_text", type: "text" });
        form.createField({ name: "c_textarea", type: "textarea" });
        form.createField({ name: "c_checkbox", type: "checkbox", text: "I accept the terms and conditions" });
        form.createField({ name: "c_file", type: "file" });
        form.createField({ name: "c_hidden", type: "hidden" });
        form.createField({ name: "c_prepend", type: "text", prepend: "@" });

        form.createField({
            name: "c_select",
            type: "select",
            food: [{ value: "1", text: "One" }],
        });

        form.createField({
            name: "c_multi_select",
            type: "multi-select",
            food: [{ value: "1", text: "One" }],
        });

        form.createField({
            name: "c_autocomplete",
            type: "autocomplete",
            autocompleteData: ["Apple", "Banana"],
        });
    </script>
<?php }]; ?>
