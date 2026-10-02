@extends($layout)

@section("head")

@endsection

@section("content")

    <h2>Reset your password</h2>
    <p>
        Someone asked to reset the password of your account. If that was
        you, choose a new password with the link below. If not, you can
        ignore this email.
    </p>
    <a href="{{ $opt["reset_link"] }}">Click this link to reset your password!</a><br> Or open: {{ $opt["reset_link"] }}
@endsection
