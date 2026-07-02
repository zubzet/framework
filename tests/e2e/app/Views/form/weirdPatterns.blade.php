<div id="form" data-test="form"></div>

<template id="extra-button-template">
    <button type="button" data-test="appended-btn" class="btn btn-secondary">Extra</button>
</template>

<div data-test="cards"></div>

<script>
    var form = Z.Forms.create({ dom: "form" });

    // --- Pattern 1: conditional wrapper visibility via closest('.form-group') ---
    // A select with an "other" option toggles the visibility of another
    // field's whole group. Relies on each field input having a
    // .form-group ancestor that wraps only that row.
    var source = form.createField({
        name: "source",
        type: "select",
        food: [
            { value: "a", text: "A" },
            { value: "other", text: "Other" },
        ],
    });
    var otherDetail = form.createField({ name: "other_detail", type: "text" });
    var otherWrapper = $(otherDetail.input).closest(".form-group");
    otherWrapper.hide();
    $(source.input).change(function() {
        otherWrapper.toggle(source.input.value === "other");
    });

    // --- Pattern 2: cascading computed value across fields ---
    var year = form.createField({ name: "year", type: "number" });
    var doubled = form.createField({ name: "doubled", type: "text" });
    $(year.input).on("input", function() {
        doubled.input.value = (Number(year.input.value) || 0) * 2;
    });

    // --- Pattern 3: append a custom element into a field's group + listener ---
    var anchor = form.createField({ name: "anchor", type: "text" });
    var clicks = form.createField({ name: "clicks", type: "hidden", value: "0" });
    var extra = $($("#extra-button-template").html());
    extra.on("click", function() {
        clicks.input.value = String(Number(clicks.input.value) + 1);
    });
    $(anchor.input).closest(".form-group").append(extra);

    // --- Pattern 4: hand-rolled multi-select (hidden JSON field + cards) ---
    var selection = form.createField({ name: "selection", type: "hidden", value: "[]" });
    ["x", "y", "z"].forEach(function(v) {
        var card = document.createElement("div");
        card.className = "card p-2 pointer";
        card.dataset.value = v;
        card.setAttribute("data-test", "card-" + v);
        card.innerText = v.toUpperCase();
        card.addEventListener("click", function() {
            var arr = JSON.parse(selection.input.value);
            var i = arr.indexOf(v);
            if (i === -1) arr.push(v); else arr.splice(i, 1);
            selection.input.value = JSON.stringify(arr);
            card.classList.toggle("border-success");
        });
        document.querySelector("[data-test=cards]").appendChild(card);
    });

    // --- Pattern 5: jQuery submit-button manipulation ---
    $(form.buttonSubmit).attr("data-test", "submit-btn");

    // --- Pattern 6: bulk write via form.fields[...].input.value ---
    window.bulkWrite = function() {
        form.fields["anchor"].input.value = "bulk";
        form.fields["year"].input.value = "5";
    };
</script>
