<div class="container py-4" data-test="blade-page">

    @auth
        <h1>Welcome, {{ user()->userId }}!</h1>
    @endauth

    @guest
        <h1>Welcome, guest!</h1>
    @endguest

    @can('view-dashboard')
        <p>You have access to view the dashboard.</p>
    @else
        <p>You do not have access to view the dashboard.</p>
    @endcan


</div>
