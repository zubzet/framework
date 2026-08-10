@extends($layout)

@section("content")
    <h2 data-test="title">Blade compatibility probe</h2>

    <!-- Literal template markers that must survive migration verbatim. -->
    <div data-test="literal-echo">@{{ notBladeEcho }}</div>
    <div data-test="literal-raw">@{!! notBladeRaw !!}</div>
    <div data-test="literal-comment">@verbatim{{-- notBladeComment --}}@endverbatim</div>

    <!-- Real PHP still runs and reads $opt. -->
    <div data-test="opt-data"><?= $opt["compatData"] ?></div>

    <!-- CSS at-rules must pass through untouched. -->
    <style>
        @media (max-width: 600px) {
            .compat-probe { color: red; }
        }
    </style>

    <!-- A JS mustache embedded in a script tag. -->
    <script>
        var compatTpl = "@{{ vueStyleBinding }}";
    </script>
@endsection
