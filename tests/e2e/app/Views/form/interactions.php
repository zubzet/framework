<?php return [ "body" => function($opt) { ?>
    <div id="form" data-test="form"></div>

    <button data-test="reset_form" id ="reset_form">
        Reset Form
    </button>

    <button data-test="reset_field_b" id ="reset_field_b">
        Reset Field B
    </button>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_a",
            type: "text",
        });

        form.createField({
            name: "field_b",
            type: "text",
        });

        form.createField({
            name: "field_c",
            type: "text",
        });

        form.createField({
            name: "field_default",
            type: "text",
            default: "DefaultValue",
        });

        form.createField({
            name: "field_select",
            type: "select",
            food: [
                { value: "one", text: "One" },
                { value: "two", text: "Two" },
                { value: "three", text: "Three" },
            ],
        });

        form.createField({
            name: "field_select_default",
            type: "select",
            default: "two",
            food: [
                { value: "one", text: "One" },
                { value: "two", text: "Two" },
                { value: "three", text: "Three" },
            ],
        });

        document.getElementById("reset_form").addEventListener("click", () => {
            form.reset();
        });

        $("#reset_field_b").click(() => {
            form.fields.field_b.reset();
        });
    </script>
<?php }]; ?>
