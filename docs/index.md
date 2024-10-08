# Getting Started with ZubZet
## Why should you use a framework for PHP development?
It is not 100% necessary to use a framework, but especially raw PHP code is usually very messy. This is mostly because the language allows for so many formatting and usage habits, that might not reflect best practices. This adds up. Using a good framework vastly improves your style, but that is not it yet. The biggest advantage is, that ZubZet adds multiple layers of abstraction on top of PHP. This allows you to create webapps in record time. Even an empty project is already capable of a login system, a dashboard, a sitemap, PDF generation, HTML templating and more.

## Using MVC
Using this framework effectively, you must understand the MVC pattern, which consists of:

1. [Controllers](core-features/controllers-and-actions) handle all the logic within your program. They do the actual computational work.

2. [Models](core-features/models) handle all interactions with your data structure. This could be referring to a database as well as file or else.

3. [Views](core-features/views) are basically templates of your content, which get propagated with the results of your controllers.

## API reference
This documentation provides a general overview about most features and best practice related topics, but if you need something more technical that explains every file, class, method and their parameters, please refer to the [API Reference](./api).