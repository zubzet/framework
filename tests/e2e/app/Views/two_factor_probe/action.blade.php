@extends($layout)

@section("content")
    <button class="btn btn-outline-primary" data-test="btn-action">Run the guarded action</button>

    <pre class="mt-3 p-2 bg-light border rounded" data-test="result"></pre>

    <script>
        $('[data-test=btn-action]').click(() => {
            Z.Request.action("probe", {}, (res) => {
                $('[data-test=result]').text("passed " + res.passedAt);
            });
        });
    </script>
@endsection
