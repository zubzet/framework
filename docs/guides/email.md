# Email Guide
In the [previous guide](layout.md.md), we learned how to create and use layouts to structure our websites effectively.

In this guide, we'll delve into the process of sending **emails**, a crucial functionality for web applications to communicate with users, whether for notifications, confirmations, or newsletters.

The ZubZet framework makes email handling straightforward and efficient. Let’s explore how to implement it!

### Resources
<details>
<summary>Controller</summary>

EmailController
```php
<?php
    class EmailController extends z_controller {

        public function action_email(Request $req, Response $res) {
           // Here we will send our Email
        }

        public function action_emailuser(Request $req, Response $re) {
            // Here we will send our Email
        }
    }
?>
```
</details>

<details>
<summary>Layout</summary>
email_layout
```php
<?php return ["layout" => function($opt, $body, $head) { ?>
    <!doctype html>
    <html lang="en">
        <head>
            <?php $head($opt); ?>
        </head>
        <body class="d-flex flex-column min-vh-100">
            <h2>EMail Layout</h2>

            <main class="container mt-5">
                <?php $body($opt); ?>
            </main>
        </body>
    </html>
<?php }] ?>
```
</details>

<details>
<summary>View</summary>
email
```php
<?php return ["body" => function ($opt) { ?>
    <h2>This is an test email</h2>
<?php }]; ?>
```
</details>

## Setting up our Application
To start working with emails, we first need the basic structure of our application. This guide provides pre-built files in the [Resources](#resources) section, including templates for controllers, layouts, and views. Using these resources ensures an organized setup and allows us to focus on sending emails.

## Setting up our Mail System
??? info "Setting up a test SMTP Server"
    To send emails during development for testing purposes, it's better to simulate email sending rather than actually sending them. To do this, simply use the following values for the attributes:

    - `mail_smtp` = ENV
    - `mail_from` = ENV
    - `mail_user` = ENV
    - `mail_password` = ENV
    - `mail_security` = false

    To verify if your emails are being sent correctly, you can navigate to `localhost:3300`. This is a default interface for email testing, which are commonly used in development environments to capture and inspect outgoing emails without actually sending them.


Before sending emails, configure your SMTP server. The configuration is located in the `z_config/z_settings.ini` file, where parameters like `mail_smtp` need to be set up.

## Sending Emails
Emails are sent from controllers, and the ZubZet framework provides two methods for this purpose:

1. `sendEmail`: This method allows you to send an email to a custom email address. It accepts the following parameters:

    1. `to`: The recipient's email address.
    2. `subject`: The email subject.
    3. `document`: The view file to use for the email body.
    4. `lang`: The language of the email.
    5. `options`: An array of values to pass to the view file.
    6. `layout`: The layout file to use for the email.
    7. `attachments`: Any files to attach to the email.

2. `sendEmailToUser`: This method is used to send an email directly to a registered user by specifying their user ID. It accepts the following parameters:

    1. `userId`: The ID of the user to whom the email will be sent.
    2. `subject`: The email subject.
    3. `document`: The view file for the email body.
    4. `options`: An array of values to pass to the view file.
    5. `layout`: The layout file for the email.

For the email layout, it is essential that the filename ends with `_layout.php`; otherwise, the file will not be recognized by the framework. This naming convention ensures the layout file is correctly located and applied during email rendering.

### Example
```php
<?php
    class EmailController extends z_controller {

        public function action_email(Request $req, Response $res) {
           $res->sendEmail(
                "user@example.com",             // Recipient
                "Welcome to our service!",      // Subject
                "email/email",                  // View file
                "en",                           // Language
                [
                    "name" => "John Doe"        // Options
                ],
                "email_layout.php",             // Layout file
            );
        }

        public function action_emailuser(Request $req, Response $re) {
            $res->sendEmailToUser(
                123,                           // User ID
                "Your subscription is active", // Subject
                "email/email",                 // View file
                [
                    "plan" => "Premium"        // Options
                ],
                "email_layout.php"             // Layout file
            );
        }
    }
?>
```