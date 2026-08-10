@extends($layout)

@section("content")
    {{-- Marks this as the userspace copy of `login`; the framework ships its own
         at IncludedComponents/views/login.blade.php. core/view-resolution asserts
         this marker is present by default (userspace wins) and absent when the
         framework copy is forced via the escape hatch. --}}
    <span data-test="login-source" hidden>app</span>

    <div id="login-error-label" data-test="error"></div>

    <input type="email" id="username" data-test="username">
    <input type="password" id="password" data-test="password">

    <button id="btnLogin" data-test="btn-login">
        Sign in
    </button>
    <a class="text-primary" href="<?= $opt["root"]; ?>login/forgot-password">
        Forgot Password?
    </a>

    <script>
        function login() {
            Z.Presets.Login("username", "password", "login-error-label");
        }

        $("#btnLogin").click(() => {
            login();
        });

        $("#username, #password").keyup((e) => {
            if(e.keyCode == 13) login();
        });
    </script>
@endsection
