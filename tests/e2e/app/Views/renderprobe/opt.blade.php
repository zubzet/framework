@extends($layout)

@section("content")
    {{-- The $opt contract (CanRenderView::legacyOptExpansion): the caller's own
         data is visible both as a top-level variable ($a) AND inside $opt, while
         the framework expansions (root/host/user/...) live only in $opt. --}}
    <span data-test="top-a">{{ $a }}</span>
    <span data-test="opt-a">{{ $opt["a"] }}</span>

    <span data-test="title">{{ $opt["title"] }}</span>
    <span data-test="root">{{ $opt["root"] }}</span>
    <span data-test="host">{{ $opt["host"] }}</span>
    <span data-test="abs-root">{{ $opt["absRoot"] }}</span>

    <span data-test="has-user">{{ isset($opt["user"]) ? "yes" : "no" }}</span>
    <span data-test="has-request">{{ isset($opt["request"]) ? "yes" : "no" }}</span>
    <span data-test="has-response">{{ isset($opt["response"]) ? "yes" : "no" }}</span>
    <span data-test="has-genlink">{{ isset($opt["generateResourceLink"]) ? "yes" : "no" }}</span>
@endsection
