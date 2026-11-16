@extends($layout)

@section("content")
    {{-- The caller's own data is visible both as a top-level variable ($a) AND
         inside $opt (CanRenderView::legacyOptExpansion). --}}
    <span data-test="top-a">{{ $a }}</span>
    <span data-test="opt-a">{{ $opt["a"] }}</span>
@endsection
