# Getting Started: Views
## What does a view do?
A view contains the html the user should see. All additional resources like css, images or javascript are also loaded from views. Views do usually **not** contain a footer, navigation, header or other elements that belong to the overall layout of the page. For this, layouts should be used as without a layout, a view can't be renderer. Read more about layouts [here](layouts).

Database access and heavy logic should not be used in the view as that belong into other parts of the application.

## Structure
A view should be placed in the `z_views` directory of your project, otherwise it will not be found when called from the render method.

It is a php file which returns an array with up to three attributes. `head` and `body` are functions that get executed in the layout at the matching place.

`head` and `body` should accept a parameter called $opt. It contains data passed into the $opt parameter of the `render` method. For the view to communicate back to a controller, asynchronous methods must be used.

## Simple example view
```php
<?php return ["body" => function($opt) { ?>
    <h1>Hello World</h1>
<?php }]; ?>

```

## Example view using a passed value / option
```php
// Controller
public function action_index(Request $req, Response $res) {
    return $res->render("employee/employee_index.php", [
        "value" => "Hello World"
    ]);
}
```
```php
// View
<?php return ["body" => function($opt) { ?>
    <h1>$opt["value"]</h1>
<?php }]; ?>
```

More examples for views can be found in [`z_framework/default/views`](https://git.zierhut-it.de/Zierhut-IT/z_framework/src/branch/DEV/default/views).
