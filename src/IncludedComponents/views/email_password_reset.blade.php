@extends($layout)

@section("head")

@endsection

@section("content")

    <h2>{{ __("email.password_reset.title", locale: $opt["locale"] ?? null) }}</h2>
    <p>{{ __("email.password_reset.text", locale: $opt["locale"] ?? null) }}</p>
    <a href="{{ $opt["reset_link"] }}">{{ __("email.password_reset.link", locale: $opt["locale"] ?? null) }}</a><br> {{ __("email.password_reset.open", locale: $opt["locale"] ?? null) }} {{ $opt["reset_link"] }}
@endsection
