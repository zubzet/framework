# Getting Started: Views
## What does a view do?
A view contains the html the user should see. All additional resources like css, images or javascript are also loaded from views. Views do usually **not** contain a footer, navigation, header or other elements that belong to the overall layout of the page. For this, layouts should be used as without a layout, a view can't be renderer. Read more about layouts [here](Using-a-layout-in-your-project).

Database access and heavy logic should not be used in the view as that belong into other parts of the application.

## Structure
A view should be placed in the `z_views` directory of your project, otherwise it will not be found when called from the render method.

It is a php file which returns an array with up to three attributes. `head` and `body` are functions that get executed in the layout at the matching place. `lang` is optional and holds [language](Translations-for-multiple-languages) data useable by `$opt["lang"]`.

`head` and `body` should accept a parameter called $opt. It contains data passed into the $opt parameter of the [`render`](https://zdoc.zierhut-it.de/classes/Response.html#method_render) method. For the view to communicate back to a controller, asynchronous methods must be used.

## Simple example view
```php
    <?php return [ "body" => function($opt) { ?>
        <h1>Hello World</h1>
    <?php }]; ?>

```

## example view using a passed value / option

```php
    // Controller
    return $res->render("view.php", [
        "text" => "Hello World",
    ]);

```
```php
    // View
    <?php return [ "body" => function($opt) { ?>
        <h1>$opt["text"]</h1>
    <?php }]; ?>

```


## Complete example view with localization
```php
    <?php return [ "head" => function($opt) { ?>

    <?php }, "body" => function($opt) { ?> 

    <?php }, "lang" => [
        "en" => [
            "key1" => "word1"
        ],
        "DE_Formal" => [
            "key1" => "wort1"
        ]
    ]]; ?>
```

More examples for views can be found in [`z_framework/default/views`](https://git.zierhut-it.de/Zierhut-IT/z_framework/src/branch/DEV/default/views).
