# Layout Guide
In the [previous guide](todo.md), we explored how to create and manage forms effectively.

In this guide, we'll focus on using **[layouts](../core-features/layouts.md)**, a powerful feature in the ZubZet framework. Layouts help you structure your application by organizing your website into distinct sections, such as a **Main Page** and an **Admin Panel**, ensuring a consistent look and feel across multiple pages.

### Resources
<details>
<summary>Controller</summary>

ViewController
```php
<?php
    class ViewController extends z_controller {

        public function action_custom(Request $req, Response $res) {
            // Here we will render our custom layout
        }

        public function action_default(Request $req, Response $res) {
            return $res->render("view/view");
        }
    }
?>
```
</details>

<details>
<summary>View</summary>
view
```blade
@extends($layout)

@section("content")
    <a>This is the view</a>
@endsection
```
</details>

## Setting up our Application
To start working with permissions, we first need the basic structure of our application. This guide provides pre-built files in the [Resources](#resources) section, including templates for controllers and views. Using these resources ensures an organized setup and allows us to focus on implementing functionality and layouts.

## Creating a Layout
Layouts in the ZubZet framework are defined as [Blade templates](../core-features/views.md) within the `z_views` folder. A layout is just the page's HTML shell with `@yield` placeholders where the view's sections are inserted.

You can add your HTML and framework-related logic within this structure.  
For instance:
```blade
<!doctype html>
<html lang="en">
    <head>
        <x-zubzet::head :opt="$opt"/>
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    </head>
    <body class="d-flex flex-column min-vh-100">
        <h2>Adminpanel</h2>

        <main class="container mt-5">

        </main>
        <x-zubzet::body :opt="$opt"/>
    </body>
</html>
```

To display specific views dynamically, utilize the `@yield("content")` and `@yield("head")` directives:
```blade
<!doctype html>
<html lang="en">
    <head>
        <x-zubzet::head :opt="$opt"/>
        @yield("head")
        <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    </head>
    <body class="d-flex flex-column min-vh-100">
        <h2>Adminpanel</h2>

        <main class="container mt-5">
            @yield("content")
        </main>
        <x-zubzet::body :opt="$opt"/>
    </body>
</html>
```

### Explanation
- The whole layout file is the html code we want to render (there is no wrapper to return).
- `@yield("head")` integrates the `<head>` section of the view.
- `@yield("content")` renders the view content within the body.

## Rendering a Layout with a View
To apply a layout to a view file, pass the layout file path as the third parameter to the `$res->render` method:
```php
<?php
    class ViewController extends z_controller {

        public function action_admin(Request $req, Response $res) {
            return $res->render("view/view", [], "view/layout");
        }

        public function action_user(Request $req, Response $res) {
            return $res->render("view/view");
        }
    }
?>
```
In this example, the same view file is rendered twice, once with a custom layout `(view/layout)` and once with the `default layout`.

## Editing the Default Layout
The framework's default layout, located at `z_views/layout/default_layout.blade.php`, is automatically used if no other layout is specified. This layout can be modified to include common components such as navigation bars, footers, or any other elements that should appear across pages without custom layouts.

## Next Guide
In the next guide, we will learn how to send emails and configure our SMTP server.

[Email](email.md)