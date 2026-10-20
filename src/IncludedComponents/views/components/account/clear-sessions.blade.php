@auth
    <button type="button" id="z-clear-sessions" {{ $attributes }} data-test="btn-clear-sessions">
        @if($slot->isEmpty())
            {{ __("account.sessions.clear") }}
        @else
            {{ $slot }}
        @endif
    </button>

    <script>
        $(() => {
            $("#z-clear-sessions").click(() => {
                Z.Request.root("_zubzet/profile/clear-sessions", "clear-sessions", {}, (res) => {
                    if("success" == res.result) location.reload();
                });
            });
        });
    </script>
@endauth
