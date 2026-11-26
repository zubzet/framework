<span data-test="outer-root">{{ $opt["root"] }}</span>
<span data-test="outer-user">{{ is_object($opt["user"]) ? "USER" : "NONE" }}</span>
<span data-test="outer-caller">{{ $opt["callerValue"] ?? "NOT_SHARED" }}</span>
<x-opt-inner/>
