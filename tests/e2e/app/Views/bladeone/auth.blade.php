@auth
    <h1>Authenticated</h1>
@endauth
@guest
    <h1>Guest</h1>
@endguest

@can("test.permission")
    <h1>Can</h1>
@endcan
@cannot("test.permission")
    <h1>Cannot</h1>
@endcannot