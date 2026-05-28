<?php return [ "body" => function($opt) { ?>
    <div id="form" data-test="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "field_multi_select_values_only",
            type: "multi-select",
            text: "Values Only",
            food: [
                { value: "one" },
                { value: "two" },
                { value: "three" },
            ],
        });

        form.createField({
            name: "field_multi_select_text_only",
            type: "multi-select",
            text: "Text Only",
            food: [
                { text: "One" },
                { text: "Two" },
                { text: "Three" },
            ],
        });

        form.createField({
            name: "field_multi_select",
            type: "multi-select",
            text: "Values + Text",
            food: <?= $opt["exampleData"] ?>,
        });

        form.createField({
            name: "field_multi_select_required",
            type: "multi-select",
            text: "Required",
            required: true,
            food: <?= $opt["exampleData"] ?>,
        });

        form.createField({
            name: "field_multi_select_preloaded",
            type: "multi-select",
            text: "Pre-loaded (inline array)",
            food: <?= $opt["exampleData"] ?>,
            value: ["one", "three"],
        });

        form.createField({
            name: "field_multi_select_preloaded_json",
            type: "multi-select",
            text: "Pre-loaded (json_encode'd)",
            food: <?= $opt["exampleData"] ?>,
            value: <?= json_encode(["two"]) ?>,
        });

        form.createField({
            name: "field_multi_select_placeholder",
            type: "multi-select",
            text: "Placeholder",
            placeholder: "Pick one...",
            food: <?= $opt["exampleData"] ?>,
        });

        form.createField({
            name: "field_multi_select_default",
            type: "multi-select",
            text: "Default",
            food: <?= $opt["exampleData"] ?>,
            default: ["two"],
        });

        form.createField({
            name: "field_multi_select_optgroup",
            type: "multi-select",
            text: "Optgroups",
            food: [
                { type: "optgroup", text: "Numbers" },
                { value: "one", text: "One" },
                { value: "two", text: "Two" },
                { type: "optgroup", text: "Letters" },
                { value: "a", text: "Alpha" },
                { value: "b", text: "Bravo" },
            ],
        });
    </script>
<?php }]; ?>
