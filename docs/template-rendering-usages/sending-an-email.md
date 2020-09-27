# Sending an email
To send an email there are two methods in response called [`sendEmail()`](https://zdoc.zierhut-it.de/classes/Response.html#method_sendEmail) and [`sendEmailToUser()`](https://zdoc.zierhut-it.de/classes/Response.html#method_sendEmailToUser).

`$subject` can be an array to serve subjects for multiple languages.
`$document` is the path to a view. Any view can be used as a mail. But it is wise to create extra ones, because script stuff won't work. Be careful not to leak data that only the requested account has access to.
`$opt` are parameters to pass into the view. 

This method uses [`render()`](https://zdoc.zierhut-it.de/classes/Response.html#method_render) internally.

For this feature to work a mail address needs to be configured in the [booter settings](https://git.zierhut-it.de/Zierhut-IT/z_framework/wiki/The-Booter-Settings) and a SMTP service needs to be available. PHP need to be setup correctly too.

Example code for sending a mail:
```php
$res->sendEmail(
    "test@sd.tld",                                         // Target address
    ["en" => "Welcome Mail", "de" => "Willkommens Mail"],  // Subject
    "email_welcome.php",                                   // Path to the email view
    "de",                                                  // Language used in the email
    ["name" => "Otto"],                                    // Options
    "layout/email_layout.php"                              // Layout to use
);
```

## Send to registered users
[`sendEmailToUser()`](https://zdoc.zierhut-it.de/classes/Response.html#method_sendEmailToUser) sends a mail to an user identified by its user id. The mail address and language are fetched from the database, so less information is needed.

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
