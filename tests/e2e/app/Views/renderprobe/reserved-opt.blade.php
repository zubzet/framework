@extends($layout)

@section("content")
    {{-- Handing the whole $opt to an app component is refused, the name is reserved. --}}
    <x-alert :opt="$opt">RESERVED_OPT</x-alert>
@endsection
