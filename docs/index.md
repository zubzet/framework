# Getting Started 
## Why should you use the framework?
It is not 100% necessary to use a framework, but especially PHP code is usually very messy. This is mostly because the language allows for so many formatting and usage habits, that might not reflect best practices. This adds up. Using a good framework vastly improves your style, but that is not it yet. The biggest advantage is, that the Z_Framework adds multiple layers of abstraction on top of PHP. This allows you to create webapps in record time. Even an empty project is already capable of a login system, a dashboard, a sitemap, PDF generation, HTML templating, etc. while disregarding all the programming based features.

## Using MVC
Using this framework effectively, you must use the MVC pattern, which consists of 
* [Controllers](./Getting-Started%3A-Controllers-and-Actions) handle all the logic within your program. They do the actual computational work. 
* [Models](./Getting-Started%3A-Models) handle all interactions with your data structure. This could be referring to a database as well as file or else.
* [Views](./Getting-Started%3A-Views) are basically templates of your content, which get propagated with the results of your controllers.

## Wiki and technical documentation
This wiki provides a general overview about most framework features and best practice related topics, but if you need something more technical that explains every file, class, method and their parameters, please refer to [zdoc](https://zdoc.zierhut-it.de/).