@extends($layout)

@section("content")
    <div id="form" data-test="form"></div>

    <pre class="mt-3 p-2 bg-light border rounded" data-test="result"></pre>

    <script>
        var show = (text) => $('[data-test=result]').text(text);

        var form = Z.Forms.create({
            dom: "form",
            name: "two_factor_probe",
            saveHook: (res) => show("saved " + res.savedAt),
            formErrorHook: () => show("formErrors"),
        });

        form.createField({
            name: "note",
            type: "text",
            default: "a note",
        });
    </script>
@endsection
