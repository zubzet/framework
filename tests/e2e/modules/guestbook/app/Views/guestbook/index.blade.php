@extends($layout)

@section("content")
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/guestbook.css"); ?>">

    <h1 data-test="guestbook-title">{{ $opt["title"] }}</h1>
    <span data-test="guestbook-skin">default</span>

    @foreach($opt["entries"] as $entry)
        <div data-test="guestbook-entry">
            <span data-test="guestbook-entry-author">{{ $entry["author"] }}</span>:
            <span data-test="guestbook-entry-message">{{ $entry["message"] }}</span>
        </div>
    @endforeach

    <form method="POST" action="/guestbook/add">
        <input type="text" name="author" placeholder="Author" maxlength="64">
        <input type="text" name="message" placeholder="Message" maxlength="255">
        <button type="submit" data-test="guestbook-submit">Sign</button>
    </form>

    @include("guestbook.footer")
@endsection
