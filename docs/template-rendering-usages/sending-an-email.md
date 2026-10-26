# Sending an email
To [send an email](../guides/email.md) there are two methods in [response](../api/classes/ZubZet-Framework-Message-Response.html) called `sendEmail()` and `sendEmailToUser()`.

`$subject` can be an array keyed by language (keys are lowercased) to serve subjects for multiple languages. The entry of `$lang` is used, falling back to `en`, so an `en` entry is required.
`$document` is the path to a [view](../core-features/views.md). Any view can be used as a mail. But it is wise to create extra ones, because script stuff won't work. Be careful not to leak data that only the requested account has access to.
`$opt` are parameters to pass into the view. There, `$opt["overwrite_lang"]` holds the lowercased `$lang` (`null` becomes `en`), `$opt["application_root"]` the host plus root folder for absolute links, and `$opt["user"]` the user of the current request, not the recipient.

This method uses `render()` internally. `render()` does not throw for a missing `$document` or layout: it catches the view-not-found error and renders the framework's `500` view in `layout/min_layout` instead, so the mail is still sent with the error page as its body. Check the view name, especially for views you ship yourself.

`sendEmail()` sends synchronously to a single recipient (no CC, BCC, reply-to, queue or retry) and returns `true` after sending. The body goes out as UTF-8 HTML with a plain-text alternative made by stripping its tags. If PHPMailer fails (SMTP unreachable, rejected login), it echoes `Message could not be sent. Mailer Error: ...` into the response output and returns `false`; nothing is logged. The default `$layout` is `email`, which resolves to `layout/email_layout` (`mail`, `mail_layout` and `layout/mail_layout.php` all name the same layout); the framework only ships `layout/mail_layout`, so create `email_layout` yourself or pass another layout.

## Bundled mail views
The framework ships three mail views, each a `.blade.php` file that a view of the same name in your `z_views` directory replaces:

- `email_verify` is sent when someone signs up or asks for a new verification link. It expects the option `url`. The subject is `<pageName> - Sign Up` and the `pageName` setting must exist in the [booter settings](../core-features/configuration.md): the framework reads it with `getBooterSettings("pageName", "")`, whose second argument is `$useDefault`, and the empty string counts as false, so a missing key throws an `InvalidArgumentException` instead of falling back to an empty prefix.
- `email_password_reset` is sent by the forgot-password flow. It expects the option `reset_link`.
- `email_too_many_logins` expects the options `date` and `ip`. `$req->ip()` can be spoofed with a request header and the view prints it unescaped, validate it first. The framework no longer sends it itself, see [Send the login warning yourself](../setup/upgrade/1.2.0-to-1.3.0.md#6-send-the-login-warning-yourself-if-you-relied-on-it).

A mail view you add yourself should not reuse these names unless it is meant to replace the bundled one. All three are written in Blade and extend `$layout`.

For this feature to work a mail address needs to be configured in the [booter settings](../core-features/configuration.md) and a SMTP service needs to be available. PHP need to be setup correctly too.

Example code for sending a mail:
```php
public function action_register(Request $req, Response $res) {
    if($req->isAction("register")) {
        $email = $req->getPost("email");
        $password = $req->getPost("password");

        $response = $req->getModel("Employee")->register($email, $password);

        if(!$response) {
            return $res->error();
        }

        $res->sendEmail(
            $email          ,                                         // Target address
            ["en" => "Welcome Mail", "de" => "Willkommens Mail"],     // Subject
            "email_welcome",                                         // Path to the email view
            "en",                                                     // Language used in the email
            [
                "email" => $email
            ],                                                        // Options
            "employee/mail_layout"                                    // Layout to use
        );
    }
}
```

### Example Layout
```blade
<html>
    <head>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        @yield("head")
    </head>
    <body>
        Welcome <?= $opt["email"] ?>!
        @yield("content")
    </body>
</html>
```


## Send to registered users
`sendEmailToUser()` sends a mail to an user identified by its user id. The mail address and language are fetched from the database, so less information is needed; if the user's language row has no value, the `anonymous_language` setting (no default) is used, and `en` if that is unset too. It returns the result of `sendEmail()`, and its default layout is the bundled `layout/mail_layout`, which only prints the view's content. It throws an `Exception` if the user does not exist or has no email address.

Exmaple: 
```php
$res->sendEmailToUser(
    1,                                                             // User ID
    ["en" => "New cooking recipes!", "de" => "Neue Kochrezepte!"], // Subject
    "email_recipes",                                              // Path to the mail view
    ["r1" => "Cake", "r2" => "Cookies!!!"],                        // Options
    "layout/email_layout"                                         // Layout to use
);
```
