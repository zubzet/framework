@extends($layout)

@section("head")

@endsection

@section("content")

    <p data-test="module-security-mail">module-security-mail</p>
    <p>
        Date:
        <?= $opt["date"] ?>
    </p>
    <p>
        IP Address: <?= $opt["ip"]; ?>
    </p>
@endsection
