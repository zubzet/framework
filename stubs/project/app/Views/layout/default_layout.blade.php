<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?= $opt["title"]; ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <x-zubzet::head :opt="$opt"/>
        @yield("head")
    </head>
    <body>
        <main class="container pt-5">
            @yield("content")
        </main>

        <x-zubzet::body :opt="$opt"/>
    </body>
</html>
