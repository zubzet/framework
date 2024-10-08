# Getting Started: Controllers and Actions
## What does a controller do?
A controller is one part of the MVC pattern. It handles all the logic, but what does that mean? Usually the controller takes in data from one or multiple models as well as user input and does something with it. This could include sorting, searching, calculating and generally making the data ready to display or ready to put into the database in case of a form input for example. 

## How to get a controller executed?
Since there are no actual paths, controllers are tightly bound to the requested url. The first part of the URL, that is not part of getting to your project's root directory, determines which Controller will be used. If you call your controller IndexController, it will be executed when no name is given by a reuqest. 

## What are actions?
Actions are basically the second part of the URL. After choosing a controller, the framework tries to find the requested method from the URL in that controller and than executes it. There are some magic action names, that are reserved for default functionalities:

| Name            | Description                                                                                    |
| --------------- | ---------------------------------------------------------------------------------------------- |
| action_index    | Will be executed if no action is specified in the request.                                     |
| action_fallback | Will be executed when the requested action is not found. Can be used to have infinite actions. |

## ExampleController.php
The following function `action_test` will be executed when requesting the path `{root}/example/test/{parameters}`.
```php
<?php
    class ExampleController extends z_controller {

        public function action_test($req, $res) {

        }

    }
?>
```