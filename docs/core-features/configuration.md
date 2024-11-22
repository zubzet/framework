# The Booter Settings

## What is this?
The booter settings are a way of getting your most basic settings. If you have something like the uploads folder or the database connection, that might be changed by a customer/admin after finishing this project, those values can be stored under your `z_config folder` as a key of `z_settings.ini`.

## How to use it
If you want to use another key, simply create a new key in your `z_settings.ini`. To recall the value use `$res->getBooterSettings("key")`. If you don't pass a key, the whole array of values will be returned.

## What do you actually put in here?
- Database connection details
- Connections to other external tools like a Mailer, Storage or Api keys.
- Basic Framework settings

## Example
```php
public function action_chatgpt(Request $req, Response $res) {
    $chatgptApiKey = $res->getBooterSettings("chatgpt_api_key");

    /* remaining code */

    return $res->render("admin/chatgpt.php");
}
```


## Purpose
This helps manage credentials and settings more easily, while keeping sensitive data separate from everything else.
