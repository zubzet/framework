# Using a layout in your project
## What is a layout?
When talking about a layout, a reusable page structure is meant. To an extend, most content pages look very similar. Think Navigation or Footer. A perfect opportunity for generalization.

## How to use one in your project?
When using `$res->render`, a third optional parameter accepts a path to a layout. See the [zdoc](https://zdoc.zierhut-it.de/classes/Response.html#method_render) for a more detailed description of the method. If no parameter is given, the render engine will look for a layout with the standard name in your views folder. The standard location for a layout is: `{your z_views folder}/layout/default_layout.php`. If you wish to use a different location, you'll need to use the third parameter to specify a different path. This also allows you to use multiple layouts within the same project and even switch dynamically for content pages. 

### Example layout
```html
<?php  return ["layout" => function($opt, $body, $head) { ?>
    <!doctype html>
    <html class="no-js" lang="en">
        <head>
            <?php $opt["layout_essentials_head"]($opt); ?>
            <?php $head($opt); ?>
        </head>
        <body>
            <?php $body($opt); ?> <!-- View ist rendered here-->
            <?php $opt["layout_essentials_body"]($opt); ?>
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