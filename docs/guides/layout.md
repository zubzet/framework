# Layout Guide
In the [previous guide](todo), we explored how to create and manage forms effectively.

In this guide, we'll focus on using **layouts**, a powerful feature in the ZubZet framework. Layouts help you structure your application by organizing your website into distinct sections, such as a **Main Page** and an **Admin Panel**, ensuring a consistent look and feel across multiple pages.

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
```php
<?php return [ "body" => function($opt) { ?>
    <a>This is the view</a>
<?php }]; ?>
```
</details>

## Setting up our Application
To start working with permissions, we first need the basic structure of our application. This guide provides pre-built files in the [Resources](#resources) section, including templates for controllers and views. Using these resources ensures an organized setup and allows us to focus on implementing functionality and layouts.

## Creating a Layout
Layouts in the ZubZet framework are defined as PHP files within the `z_views` folder. Below is the basic structure of a layout file:
```html
<?php return ["layout" => function($opt, $body, $head) { ?>

<?php }] ?>
```

You can add your HTML and framework-related logic within this structure.  
For instance:
```html
<?php return ["layout" => function($opt, $body, $head) { ?>
    <!doctype html>
    <html lang="en">
        <head>
            <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        </head>
        <body class="d-flex flex-column min-vh-100">
            <h2>Adminpanel</h2>

            <main class="container mt-5">

            </main>
        </body>
    </html>
<?php }] ?>
```

To display specific views dynamically, utilize the `$body` and `$head` variables:
```html
<?php return ["layout" => function($opt, $body, $head) { ?>
    <!doctype html>
    <html lang="en">
        <head>
            <?php $head($opt); ?>
            <link rel="stylesheet" href="assets/css/bootstrap.min.css">
        </head>
        <body class="d-flex flex-column min-vh-100">
            <h2>Adminpanel</h2>

            <main class="container mt-5">
                <?php $body($opt); ?>
            </main>
        </body>
    </html>
<?php }] ?>
```

### Explanation
- Everything between `<?php return ["layout" => function($opt, $body, $head) { ?>` and `<?php }] ?>` will be our html code we want to render.
- `<?php $head($opt); ?>` integrates the `<head>` section of the view.
- `<?php $body($opt); ?>` renders the view content within the body.

## Rendering a Layout with a View
To apply a layout to a view file, pass the layout file path as the third parameter to the `$res->render` method:
```php
<?php
    class ViewController extends z_controller {

        public function action_admin(Request $req, Response $res) {
            return $res->render("view/view", [], "view/layout.php");
        }

        public function action_user(Request $req, Response $res) {
            return $res->render("view/view");
        }
    }
?>
```
In this example, the same view file is rendered twice—once with a custom layout `(view/layout.php)` and once with the `default layout`.

## Editing the Default Layout
The framework's default layout, located at `z_views/layout/default_layout.php`, is automatically used if no other layout is specified. This layout can be modified to include common components such as navigation bars, footers, or any other elements that should appear across pages without custom layouts.

## Next Guide
In the next guide, we will learn how to send emails and configure our SMTP server.

[Email](email)