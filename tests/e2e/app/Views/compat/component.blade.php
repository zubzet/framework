@extends($layout)

@section("content")
    <h2 data-test="title">Anonymous component probe</h2>

    {{-- Authored .blade.php (not migrated): exercises Katana's anonymous component
         syntax through the framework's Katana engine adapter. --}}
    <x-alert type="warning" data-test="from-component">Component slot works {{ 1 + 2 }}</x-alert>
@endsection
