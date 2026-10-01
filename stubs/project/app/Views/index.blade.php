@extends($layout)

@section("content")
    <h1>ZubZet</h1>

    <ul>
        @foreach($opt["examples"] as $example)
            <li>{{ $example["name"] }}</li>
        @endforeach
    </ul>
@endsection
