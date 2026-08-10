<!doctype html>
<html class="no-js" lang="en">
    <head>
        <x-zubzet::head :opt="$opt"/>
        @yield("head")
    </head>
    <body>
        <h2>Alt Layout</h2>
        @yield("content")
    </body>
</html>
