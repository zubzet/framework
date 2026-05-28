<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({ dom: "form" });

        // CED widget matching the Response::doCED contract:
        //   table z_probe_ced has columns (id, name, note, active)
        //   each sub-row carries Z=create|edit|delete + name + note
        //   (+ dbId for edit/delete)
        form.createCED({
            name: "items",
            text: "Items",
            fields: [
                { name: "name", type: "text", text: "Name" },
                { name: "note", type: "text", text: "Note" },
            ],
            value: <?= $opt["items"] ?>,
        });
    </script>
<?php }]; ?>
