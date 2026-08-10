@extends($layout)

@section("content")
    <div id="named-form" data-test="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "named-form",
            name: "named-action",
        });

        form.createField({
            name: "named_value",
            type: "text",
        });
    </script>
@endsection
