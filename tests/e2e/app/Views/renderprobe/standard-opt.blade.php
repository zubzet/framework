@extends($layout)

@section("content")
    {{-- A component has the framework's standard $opt without being handed anything,
         nested ones too. What the controller passed to render() is not part of it. --}}
    <span data-test="view-root">{{ $opt["root"] }}</span>
    <span data-test="view-caller">{{ $opt["callerValue"] }}</span>
    <x-opt-outer/>
@endsection
