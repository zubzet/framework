@extends($layout)

@section("content")
    <div id="fallback-form" data-test="form"></div>

    <script>
        var form = Z.Forms.create({
            dom: "fallback-form",
        });

        form.createField({
            name: "fallback_value",
            type: "text",
        });
    </script>
@endsection
