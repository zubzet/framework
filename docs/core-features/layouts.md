# Using a layout in your project
## What is a layout?
When talking about a layout, a reusable page structure is meant. To an extend, most content pages look very similar. Think Navigation or Footer. A perfect opportunity for generalization.

## How to use one in your project?
When using `$res->render`, a third optional parameter accepts a path to a layout. If no parameter is given, the render engine will look for a layout with the standard name in your views folder. The standard location for a layout is: `{your z_views folder}/layout/default_layout.php`. If you wish to use a different location, you'll need to use the third parameter to specify a different path. This also allows you to use multiple layouts within the same project and even switch dynamically for content pages. 

### Example layout
```html
<?php return ["layout" => function($opt, $body, $head) { ?>
<!doctype html>
<html lang="en">
    <head>
        <?php $head($opt); ?>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    </head>
    <body class="d-flex flex-column min-vh-100">
        <header>
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand">Adminpage</a>
            </nav>
        </header>

        <main class="container mt-5">
            <?php $body($opt); ?>
        </main>

        <footer class="my-2 footer fixed-bottom">
            <p class="text-center text-body-secondary">© 2024 Company</p>
        </footer>
    </body>
</html>
<?php }, "lang" => [
    "en" => [
        "key" => "word",
        "key2" => "word2"
    ],
    "DE_Formal" => [
        "key" => "wort",
        "key2" => "wort2"
    ]
]]; ?>

```

### Example usage
```php
public function action_index(Request $req, Response $res) {
    return $res->render("admin/index.php", [], "admin/layout.php");
}
```