@extends($layout)

@section("content")
    {{-- @auth / @guest are bound by Rendering\Katana\Hooks to the framework's
         permission model: the directive argument is a permission (dotted,
         wildcard-aware), and @guest is the negation of @auth. --}}
    @auth
        <span data-test="auth">AUTH</span>
    @endauth
    @guest
        <span data-test="guest">GUEST</span>
    @endguest

    @auth("dashboard")
        <span data-test="auth-dashboard">AUTH_DASHBOARD</span>
    @endauth
    @auth("orders.view")
        <span data-test="auth-orders">AUTH_ORDERS</span>
    @endauth

    @guest("dashboard")
        <span data-test="guest-dashboard">GUEST_DASHBOARD</span>
    @endguest
    @guest("orders.view")
        <span data-test="guest-orders">GUEST_ORDERS</span>
    @endguest
@endsection
