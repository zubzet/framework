@extends($layout)

@section("content")
    <div id="form" data-test="form"></div>

    <script>
        var form = Z.Forms.create({ dom: "form" });

        form.createField({
            name: "city",
            type: "autocomplete",
            autocompleteData: "Form/autocomplete",
        });
    </script>
@endsection
