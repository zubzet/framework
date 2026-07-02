<div id="form" data-test="form"></div>

<script>
    var form = Z.Forms.create({ dom: "form" });

    form.createField({ name: "agree", type: "checkbox", text: "I agree" });
    form.createField({ name: "subscribed", type: "checkbox", text: "Subscribe", default: true });
    form.createField({ name: "terms", type: "checkbox", text: "Accept the terms" });

    $(form.buttonSubmit).attr("data-test", "submit-btn");
</script>
