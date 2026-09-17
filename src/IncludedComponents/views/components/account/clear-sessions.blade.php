@auth
    <button type="button" id="z-clear-sessions" {{ $attributes }} data-test="btn-clear-sessions">
        @if($slot->isEmpty())
            Clear all sessions
        @else
            {{ $slot }}
        @endif
    </button>

    <script>
        $(() => {
            $("#z-clear-sessions").click(() => {
                Z.Request.root("z/profile", "clear-sessions", {}, (res) => {
                    if("success" == res.result) location.reload();
                });
            });
        });
    </script>
@endauth
