# Sending an email
To send an email there are two methods in response called `sendEmail()` and `sendEmailToUser()`.

`$subject` can be an array to serve subjects for multiple languages.
`$document` is the path to a view. Any view can be used as a mail. But it is wise to create extra ones, because script stuff won't work. Be careful not to leak data that only the requested account has access to.
`$opt` are parameters to pass into the view. 

This method uses `render()` internally.

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
            "email_welcome.php",                                      // Path to the email view
            "en",                                                     // Language used in the email
            [
                "email" => $email
            ],                                                        // Options
            "employee/mail_layout.php"                                // Layout to use
        );
    }
}
```

### Example Layout
```php
<?php return ["layout" => function($opt, $body, $head) { ?>
    <html>
        <head>
            <meta charset="utf-8"/>
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <?= $head($opt); ?>
        </head>
        <body>
            Welcome <?= $opt["email"] ?>!
            <?= $body($opt); ?>
        </body>
    </html>
<?php }]; ?>
```


## Send to registered users
`sendEmailToUser()` sends a mail to an user identified by its user id. The mail address and language are fetched from the database, so less information is needed.

Exmaple: 
```php
$res->sendEmailToUser(
    1,                                                             // User ID
    ["en" => "New cooking recipes!", "de" => "Neue Kochrezepte!"], // Subject
    "email_recipes.php",                                           // Path to the mail view
    ["r1" => "Cake", "r2" => "Cookies!!!"],                        // Options
    "layout/email_layout.php"                                      // Layout to use
);
```
