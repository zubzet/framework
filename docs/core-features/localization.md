# Translations for multiple languages
The framework has functions to create the same pages in multiple languages from one view. Nothing outside the view needs to be changed to use this feature. The system is simple and can only match sentences to keys. Its **not possible** to do advanced stuff like getting plural forms automatically.
## Defining keys
To define language keys, add a `lang` index to your view return array. In `lang` you can add multiple languages and keys to them.
```php
return [
    "head" => function($opt) { ... },
    "body" => function($opt) { ... },
    "lang" => [
        "de" => [
            "roles" => "Rollen",
            "name" => "Name",
            "permissions" => "Berechtigungen",
            "permission" => "Berechtigung",
            "delete_confirm" => "Möchten Sie wirklich die Rolle löschen?",
            "delete" => "Rolle Löschen"
        ],
        "en" => [
            "roles" => "Roles",
            "name" => "Name",
            "permissions" => "Permissions",
            "permission" => "Permission",
            "delete_confirm" => "Do you really want to delete this role?",
            "delete" => "Delete role"
        ]
    ]
];
```
Keys can also be defined inside the layout. Then they will be available in all views using that layout. To do that simply, add this lang index in the return array of the layout.
## Using keys
In the view you can access the keys with the `lang` method in `opt`.
```php
<span><?php $opt["lang"]("roles") ?>: 5</span>
```
`$opt["lang"]($key, $doEcho)` will echo the value by default. If the second parameter is set to false, the value will be returned instead of echoed.
