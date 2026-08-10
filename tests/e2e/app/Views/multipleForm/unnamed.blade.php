@extends($layout)

@section("content")
    <div data-test="form"></div>

    <script>
        var form = Z.Forms.create({});
        document.querySelector("[data-test=form]").appendChild(form.dom);

        form.createField({
            name: "unnamed_value",
            type: "text",
        });
    </script>
@endsection
