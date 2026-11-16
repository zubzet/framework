@extends($layout)

@section("head")

@endsection

@section("content")

    <h2>{{ __("email.organization_invite.title", locale: $opt["locale"] ?? null) }}</h2>
    <p>{{ __("email.organization_invite.text", ["{organization}" => $opt["organization"]], locale: $opt["locale"] ?? null) }}</p>
    <a href="{{ $opt["invite_link"] }}">{{ __("email.organization_invite.link", locale: $opt["locale"] ?? null) }}</a><br> {{ __("email.organization_invite.open", locale: $opt["locale"] ?? null) }} {{ $opt["invite_link"] }}
@endsection
