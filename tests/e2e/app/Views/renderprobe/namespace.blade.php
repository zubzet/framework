@extends($layout)

@section("content")
    {{-- <x-zubzet::head/> must resolve to the FRAMEWORK component (registered under
         the "zubzet" namespace), while the plain <x-head/> resolves to the APP
         component of the same name. Neither shadows the other (katanaphp/blade#66):
         the namespaced tag can only resolve inside its namespace, and the plain tag
         resolves userspace-first. --}}
    <x-zubzet::head :opt="$opt"/>
    <x-head/>
@endsection
