<!doctype html>
<html class="no-js">
    <head>
        <x-zubzet::head :opt="$opt"/>
        @yield("head")
    </head>
    <body id="top" data-test="dashboard-top">
        <div class="container py-5">
            @yield("content")
        </div>

        <x-zubzet::body :opt="$opt"/>
    </body>
</html>
