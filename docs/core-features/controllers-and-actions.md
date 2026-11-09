# Getting Started: Controllers and Actions

## What does a controller do?
A controller is one part of the [MVC pattern](mvc.md). It handles all the logic, but what does that mean? Usually the controller takes in data from one or multiple [models](models.md) as well as user input and does something with it. This could include sorting, searching, calculating and generally making the data ready to display or ready to put into the database in case of a form input for example. 

## How to get a controller executed?
Since there are no actual paths, controllers are tightly bound to the requested url. The first part of the URL, that is not part of getting to your project's root directory, determines which Controller will be used. If the request has no path segments, the controller named by the `defaultIndex` setting in `z_config/z_settings.ini` is executed with `action_index`. The value is the full controller class name, used as written, and when the key is unset it falls back to `DashboardController`, which the framework does not ship (without an application one `/` ends in a 404). New projects ship with `defaultIndex = DashboardController`. To serve `localhost/` from the bundled `IndexController` (renders `index.php` in `layout/default_layout.php`), set `defaultIndex = IndexController`; without that it is only reachable at `/index`.



## File Routing

The url is separated into 4 relevant parts

- Host/root for example `localhost/` or `www.example.com/`
- Controller name
- Action name
- Parameters

`localhost/example/test/123/321`

This would call the `action_test` in the `ExampleController.php`  with the parameters 123 and 321.

### Controller Names and Case

The controller name is built from the first URL segment: only its first letter is upper-cased, the rest is kept exactly as sent, and `Controller` is appended.

| URL             | Looks for                  |
| --------------- | -------------------------- |
| `/example/test` | `ExampleController`        |
| `/Example/test` | `ExampleController`        |
| `/EXAMPLE/test` | `EXAMPLEController`        |
| `/frameworkApi` | `FrameworkApiController`   |

The file is found by this exact spelling, so on case-sensitive file systems (Linux) the file name must match it: `ExampleController.php` answers `/example` and `/Example`, but not `/EXAMPLE`, which ends in a 404. A controller with capitals inside its name (`FrameworkApiController`) is only reachable with the same capitals in the URL (`/frameworkApi`). The class name has to equal the file name without `.php` and lives in the global namespace (no `namespace` line). A controller usually extends `z_controller`, the alias of `ZubZet\Framework\Core\Controller` (which only adds `makeFood()` and `makeCEDFood()`; the bundled `ErrorController` does not extend it). An optional `__construct(Request $req, Response $res)` runs on a fresh instance before every action and every [middleware](routing.md#middleware-and-aftermiddleware) or afterMiddleware method. `Request`, `Response` (`Message\*`), `Rest` (`Support\Rest`), `User` (the request user) and `z_db` (`Database\Connection`) are global class aliases that work like the fully qualified names in action signatures and route-closure type hints, so app code cannot declare global classes with these names.

Before the lookup, `-` in the segment becomes `_`, `.` becomes `§2E` and `ä`, `ö`, `ü` become `ae`, `oe`, `ue` (`/my-page` looks for `My_pageController`, `/data/file.json` runs `action_file§2Ejson`). The action name is the second segment, lower-cased, behind `action_` (a public method; `action_index` when there is no second segment); PHP method names are case-insensitive, so `action_byId` also answers `/group/byid`. The HTTP method is not checked, any verb reaches any `action_*` method.

The segment is URL-decoded once and then looked up as a file name in the controller folders (userspace, modules, framework), with `.php` appended; it is not checked against a list of allowed names. Because `.` is replaced as described above, `..` can never climb out of those folders. An encoded slash (`%2F`) is kept, though, and addresses a subfolder of a controller folder (`/Admin%2FUsers` looks for `Admin/UsersController.php`), but the default Apache setup (`AllowEncodedSlashes Off`) already answers such requests with a 404 before the framework runs. A name without a slash that is not found directly is searched for by file name in the subfolders of each controller folder, so a controller in a subfolder is reached by its plain name.


## Default Actions

To handle default behavior there are predefined actions.

| Name            | Description                                                                                    |
| --------------- | ---------------------------------------------------------------------------------------------- |
| action_index    | Will be executed if no action is specified in the request.  `localhost/example/`               |
| action_fallback | Will be executed when the requested action is not found (also for an explicit route or middleware method that does not exist), without it the 404 page is shown. Can be used to have infinite actions.  `localhost/example/ghdafiehihwe`|



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
            return $res->render("employee/index");
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
            return $res->render("employee/employee_list", [
                "employees" => $req->getModel("Employee")->getAll()
            ]);
        }

        // This action will be executed when requesting the path `{root}/Employee/view`
        public function action_view(Request $req, Response $res) {
            $req->checkPermission("employee.view");

            // Retrives URL parameters (Offset, Length)
            $employeeId = $req->getParameters(0, 1);

            $employee = $req->getModel("Employee")->getById($employeeId);

            return $res->render("employee/employee_view", [
                "employee" => $employee
            ]);
        }
    }
?>
```

## Redirecting

`$res->rerouteUrl($url = "", $root = null)` sends a `location:` header (implicit 302) and ends the script, as do `$res->logout()` and the REST helpers. `$root` defaults to the `rootFolder` setting, which is `/` when `rootDirectory` is empty and `/{rootDirectory}/` otherwise. The header is built as `$root . $url` without any slash normalisation, so `$url` has to be written relative to the root folder, without a leading slash: `rerouteUrl("employee/list")`. A leading slash is not removed, so `rerouteUrl("/employee")` yields `//employee` with an empty root directory, which browsers read as a protocol-relative URL pointing at the host `employee`. Calling it without arguments redirects to the root folder; pass `$root = ""` to redirect to an absolute or external URL. `$res->reroute($path, $alias = false, $final = false)` instead dispatches internally to another controller and action without an HTTP redirect, so the browser URL stays (the query string is kept); explicit routes and group middleware apply to the new path, and `$final = true` ends the script afterwards, otherwise the calling action continues when `reroute()` returns (as after `render()` or `json()`). The new `Request` keeps GET, POST, cookies and body but has an empty `store` and `urlParameters`. For `$alias` see [aliases](../advanced-features/aliases-and-virtual-links.md). `Response` has no helpers for HTTP status codes, custom headers or downloads: use PHP's `http_response_code()` and `header()`.

## See also

- [Routing](routing.md): registering explicit route definitions instead of name-based routing
- [Views](views.md): the templates rendered by `$res->render`
- [Permission System](permission-system.md): the permissions behind `$req->checkPermission`
- [Parameter Abstraction](parameter-abstraction.md): reading URL parameters and POST data
- API reference: [Request](../api/classes/ZubZet-Framework-Message-Request.html) and [Response](../api/classes/ZubZet-Framework-Message-Response.html)