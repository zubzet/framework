<?php return [ 'body' => function($opt) { ?>
    <div id="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "name",
            type: "text",
            text: "Name",
            width: 4,
            placeholder: "Name",
            required: true,
            value: "<?= e($opt["member"]["name"] ?? "") ?>"
        });

        form.createField({
            name: "age",
            type: "number",
            text: "Alter",
            width: 4,
            attributes: {
                min: 1,
                max: 150,
            },
            value: "<?= e($opt["member"]["age"] ?? "") ?>"
        });

        form.createField({
            name: "birthday",
            type: "date",
            text: "Geburtstag",
            width: 4,
            value: "<?= e($opt["member"]["birthday"] ?? "") ?>"
        });

        form.createField({
            name: "email",
            type: "email",
            text: "EMail",
            width: 12,
            value: "<?= e($opt["member"]["email"] ?? "") ?>"
        });

        form.createField({
            name: "note",
            text: "Notiz",
            type: "textarea",
            value: "<?= e($opt["member"]["note"] ?? "") ?>"
        });

        form.createField({
            name: "gender",
            text: "Geschlecht",
            type: "select",
            value: "<?= e($opt["member"]["genderId"] ?? "") ?>",
            food: <?= ($opt["genders"]) ?>
        });

        form.createField({
            name: "github",
            text: "Github Profillink",
            type: "text",
            value: "<?= e($opt["member"]["github"] ?? "") ?>"
        });

        $(form.buttonSubmit).html("Save");

        form.saveHook = (res) => {
            location.href = "<?= $opt["root"] ?>form/validate/" + res.memberId;
        };

    </script>
<?php }]; ?>