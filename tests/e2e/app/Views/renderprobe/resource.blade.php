@extends($layout)

@section("content")
    {{-- $opt["generateResourceLink"]($url, $root = true) echoes
         "<root?>$url?v=<assetVersion|time()>". Emitted with the root prefix on and
         off so the test can observe the toggle and the version-busting. --}}
    <span data-test="link-root"><?php $opt["generateResourceLink"]("demo/asset.js"); ?></span>
    <span data-test="link-noroot"><?php $opt["generateResourceLink"]("demo/asset.js", false); ?></span>
@endsection
