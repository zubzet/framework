# Getting Started: Controllers and Actions

## What does a controller do?
A controller is one part of the MVC pattern. It handles all the logic, but what does that mean? Usually the controller takes in data from one or multiple models as well as user input and does something with it. This could include sorting, searching, calculating and generally making the data ready to display or ready to put into the database in case of a form input for example. 

## How to get a controller executed?
Since there are no actual paths, controllers are tightly bound to the requested url. The first part of the URL, that is not part of getting to your project's root directory, determines which Controller will be used. If you call your controller IndexController, it will be executed when no name is given by a reuqest. 



## Routing

The url is separated into 4 relevant parts

- Host/root for example `localhost/` or `www.example.com/`
- Controller name
- Action name
- Parameters

`localhost/example/test/123/321`

This would call the `action_test` in the `exampleController.php`  with the parameters 123 and 321.


## Default Actions

To handle default behavior there are predefined actions.

| Name            | Description                                                                                    |
| --------------- | ---------------------------------------------------------------------------------------------- |
| action_index    | Will be executed if no action is specified in the request.  `localhost/example/`               |
| action_fallback | Will be executed when the requested action is not found. Can be used to have infinite actions.  `localhost/example/ghdafiehihwe`|



## Basic Controller

The following function `action_test` will be executed when requesting the path `{root}/example/test/{parameters}`.

```php
<?php
    class ExampleController extends z_controller {

        public function action_test(Request $req, Response $res) {

        }

    }
?>
```

## Advanced Controller Example

```php
<?php
    class EmployeeController extends z_controller {


        // This action will be executed when requesting the path `{root}/Employee`
        public function action_index(Request $req, Response $res) {
            return $res->render("employee/index.php");
        }

        // This action will be executed when requesting the path `{root}/Employee/list`
        public function action_list(Request $req, Response $res) {
            // Check the permissions of the currently logged-in user.
            $req->checkPermission("employee.list");

            // Handle an asynchronous POST-request with the parameter: action=delete-employee
            if ($req->isAction("delete-employee")) {
                $req->checkPermission("employee.delete");

                $employeeId = $req->getPost("employeeId");

                // Interacting with Models
                $req->getModel("Employee")->deleteById($employeeId);

                return $res->success();
            }

            // Rendering the View with all Employees as Array
            return $res->render("employee/employee_list.php", [
                "employees" => $req->getModel("Employee")->getAll()
            ]);
        }

        // This action will be executed when requesting the path `{root}/Employee/view`
        public function action_view(Request $req, Response $res) {
            $req->checkPermission("employee.view");

            // Retrives URL parameters (Offset, Length)
            $employeeId = $req->getParameters(0, 1);

            $employee = $req->getModel("Employee")->getById($employeeId);

            return $res->render("employee/employee_view.php", [
                "employee" => $employee
            ]);
        }
    }
?>
```