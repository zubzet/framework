# Using a layout in your project
## What is a layout?
When talking about a layout, a reusable page structure is meant. To an extent, most content pages look very similar. Think navigation or footer. A perfect opportunity for generalization. A [view](views.md) is rendered *into* a layout: the layout is the page shell, the view fills in the content.

## How a layout works
A layout is a `.blade.php` file that defines the page shell and marks where a view's sections go with `@yield`:
```blade
<!doctype html>
<html lang="en">
    <head>
        <x-zubzet::head :opt="$opt"/>
        @yield("head")
        <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/bootstrap.min.css">
    </head>
    <body class="d-flex flex-column min-vh-100">
        <header>
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand">Adminpage</a>
            </nav>
        </header>

        <main class="container mt-5">
            @yield("content")
        </main>

        <footer class="my-2 footer fixed-bottom">
            <p class="text-center text-body-secondary">© 2024 Company</p>
        </footer>

        <x-zubzet::body :opt="$opt"/>
    </body>
</html>
```
- `@yield("content")` is where the view's `@section("content")` lands, the main page body.
- `@yield("head")` is where the view's optional `@section("head")` lands, inside the document `<head>`. A layout without it drops the view's head section.
- `<x-zubzet::head :opt="$opt"/>` and `<x-zubzet::body :opt="$opt"/>` pull in the framework essentials (a `<meta charset>` and viewport tag, jQuery, Popper, Bootstrap, `bs-custom-file-input`, Font Awesome including its v4 class names, Z.js with `Z.Request.rootPath`, `rootHost` and `absRoot`, a watcher that reloads the page once the `z_login_token` cookie is gone, and the [debug bar](debug-bar.md)). Include them in every full page layout: the head component inside `<head>` before `@yield("head")`, so view heads can use jQuery and `Z`, the body component at the end of `<body>`. Pass the render data through with `:opt="$opt"`; `:custom-bootstrap="true"` on the head component skips only the Bootstrap JS tag. Z.js needs jQuery, the global `bsCustomFileInput`, Bootstrap 4 and Font Awesome, which the head component loads.

A view then selects and fills this layout:
```blade
@extends($layout)

@section("head")
    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/page.css">
@endsection

@section("content")
    <h1>Dashboard</h1>
@endsection
```
Because the view carries `@extends($layout)` rather than naming a specific layout, the same view works with whatever layout you hand it, which is what makes switching layouts per request possible.

## How to use one in your project?
When using [`$res->render`](../api/classes/ZubZet-Framework-Rendering-CanRenderView.html#method_render), a third optional parameter accepts a path to a layout, or an array whose only read key is `layout`. If no parameter is given, the render engine will look for a layout with the standard name in your views folder. The standard location for a layout is `{your z_views folder}/layout/default_layout.blade.php`. If you wish to use a different location, you'll need to use the third parameter to specify a different path. This also allows you to use multiple layouts within the same project and even switch dynamically for content pages. The framework bundles `layout/default_layout` (the default), `layout/min_layout` (the same head but the framework body scripts after the content, used by the login and the 403, 404 and 500 pages), and `layout/empty` and `layout/mail_layout` (only the view's content without any HTML, for partials and mails; `mail_layout` is the default of `sendEmailToUser()`). A same-named file in your views folder replaces a bundled one. The default and min layout link `{root}assets/img/favicon.png`, a static file your app has to supply.

### Example usage
```php
public function action_index(Request $req, Response $res) {
    return $res->render("admin/index", [], "admin/layout");
}
```

## Setting a default layout for part of your app
When `$res->render` is called without an explicit layout, the framework picks one in this order: per-instance default → global default → `layout/default_layout`. Use [`$res->setDefaultLayout("admin/layout")`](../api/classes/ZubZet-Framework-Rendering-Resolver-DefaultLayout.html#method_setDefaultLayout) (the request's single `Response`) or the static `Response::setGlobalDefaultLayout("admin/layout")` from a controller `__construct`, a [route middleware](routing.md#middleware-and-aftermiddleware) or an action to change that default for a section of the app; both `set` methods discard any layouts pushed before, so the given one becomes the only entry. Both scopes also expose `pushDefaultLayout` / `popDefaultLayout` (and the global equivalents) so nested components can install a layout and restore the previous one when they're done: `pop` removes and returns the top entry and throws an `\UnderflowException` on an empty stack, so every push needs exactly one pop, in LIFO order.
