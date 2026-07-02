<div id="form" data-test="form"></div>

<script>
    var form = Z.Forms.create({ dom: "form" });

    form.createField({
        name: "field_list_length",
        type: "multi-select",
        text: "Length 1..2",
        food: <?= $opt["options"] ?>,
    });

    form.createField({
        name: "field_list_regex",
        type: "multi-select",
        text: "Lowercase-only regex (per item)",
        food: <?= $opt["mixedCase"] ?>,
    });

    form.createField({
        name: "field_list_in_array",
        type: "multi-select",
        text: "Allow-list ->in (multi-select)",
        food: <?= $opt["options"] ?>,
    });

    form.createField({
        name: "field_list_in_select",
        type: "select",
        text: "Allow-list ->in (plain select)",
        food: <?= $opt["options"] ?>,
    });

    form.createField({
        name: "field_list_exists_multi",
        type: "multi-select",
        text: "DB exists ->exists (multi-select)",
        food: [
            { value: "fwapi_KnownRole", text: "fwapi_KnownRole" },
            { value: "fwapi_RoleState", text: "fwapi_RoleState" },
            { value: "NoSuchRole_NotSeeded", text: "NoSuchRole_NotSeeded" },
        ],
    });
</script>
