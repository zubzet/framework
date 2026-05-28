<?php return [ "body" => function($opt) { ?>
    <div id="form" data-test="form"></div>

    <!-- Listener-survival probes: incremented by the watched field's
         handlers, asserted by the spec. -->
    <div data-test="native-count">0</div>
    <div data-test="jquery-count">0</div>

    <!-- Second form with a CED, for the ZCED-stub / getValues-skip tests. -->
    <div id="ced-form" data-test="ced-form"></div>

    <script>
        var form = Z.Forms.create({ dom: "form" });

        form.createField({
            name: "field_a",
            type: "text",
        });

        form.createField({
            name: "field_disabled",
            type: "text",
            disabled: true,
        });

        form.createField({
            name: "field_hidden",
            type: "text",
            hidden: true,
        });

        // The watched field carries two listeners — one via the native
        // Z.js field.on() API, one via jQuery on field.input — so the
        // spec can prove BOTH styles survive a _updateLayout() rebuild.
        var nativeCount = 0;
        var jqueryCount = 0;
        var watched = form.createField({
            name: "watched",
            type: "text",
        });
        watched.on("input", function() {
            nativeCount++;
            document.querySelector("[data-test=native-count]").innerText = nativeCount;
        });
        $(watched.input).on("input", function() {
            jqueryCount++;
            document.querySelector("[data-test=jquery-count]").innerText = jqueryCount;
        });

        // Interleaved non-field content — must survive a layout rebuild.
        form.addCustomHTML('<span data-test="custom-marker">CUSTOM</span>');
        form.addSeperator();

        // Ends on a partial (width 6) row so a createField() issued after a
        // rebuild lands in a stale/detached row if the row-state desync
        // regressed.
        form.createField({
            name: "field_half",
            type: "text",
            width: 6,
        });

        $(form.buttonSubmit).attr("data-test", "submit-btn");

        // CED form (separate, so the main form keeps doReload=false).
        var cedForm = Z.Forms.create({ dom: "ced-form" });
        cedForm.createField({
            name: "ced_text",
            type: "text",
        });
        cedForm.createCED({
            name: "ced_items",
            text: "Items",
            fields: [
                { name: "label", type: "text" },
            ],
        });
    </script>
<?php }]; ?>
