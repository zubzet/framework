# Routing in ZubZet

Since version **1.0.0**, ZubZet not only includes the internal routing system ([controller-based actions](controllers-and-actions.md)) but also provides a route definition system.  
The routing definition system is powered by [FastRoute](https://github.com/nikic/FastRoute). *(ZubZet 1.2.0 switched the underlying engine from Slim to FastRoute — your route definitions are unchanged.)*

---

## What does "Routing" mean?

Routing is the process of defining how your application responds to different HTTP requests.
When a client (e.g., a browser or API consumer) sends a request to your server, the router decides **which piece of code (controller and method)** should handle that request.
This makes it possible to build clean, structured, and maintainable applications where every endpoint has a clear definition.

---

## How to use the routing system?

To define your routes, you first need a `Routes` folder in your project’s **app directory**.
Inside this folder, you can create multiple files. Each file can contain routes, and all of them will be automatically registered by the framework. Every `*.php` file directly in `app/Routes/` is loaded (subdirectories are not) in alphabetical order, before the module and framework route files, and the file name carries no meaning. A request is matched against these routes first (by `REQUEST_METHOD`, without a method-override parameter, and the URL-decoded path without query string and trailing slash); only if none matches, it falls back to the [controller-based actions](controllers-and-actions.md) (or the console on the CLI), so a wrong HTTP verb never produces a 405. There is no route cache, the route files run on every request, and a duplicate or shadowed route throws a FastRoute `BadRouteException` while they load.

---

## How to register a route?

In a route file, after `use ZubZet\Framework\Routing\Route;`, you can register a route with the following syntax:

```php
Route::{method}({endpoint}, [{ControllerClass}::class, {ControllerAction}]);
```

### Explanation

1. **`method`** → One of the supported [HTTP methods](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Methods):

    - `any(endpoint, action)` → Match `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS` and `HEAD`
    - `get(endpoint, action)` → Match `GET` requests
    - `post(endpoint, action)` → Match `POST` requests
    - `put(endpoint, action)` → Match `PUT` requests
    - `delete(endpoint, action)` → Match `DELETE` requests
    - `patch(endpoint, action)` → Match `PATCH` requests
    - `options(endpoint, action)` → Match `OPTIONS` requests
    - `define(method, endpoint, action)` → Define any method (upper-cased, `ANY` works too); there is no `head()` helper and `get()` does not register `HEAD`

2. **`endpoint`** → The URL path where the route will be accessible (e.g., `/users`, `/products/{id}`), in [FastRoute](https://github.com/nikic/FastRoute) syntax: `{id:\d+}` restricts a placeholder with a regex (default `[^/]+`) and a trailing `[...]` segment is optional (`/users[/{id}]`).  
   You can also define [**Route Parameter**](#route-parameters) using curly braces (e.g., `/users/{id}`), which you read with `$req->getRouteParameter()`. Group prefixes and endpoint are concatenated, a leading slash is added, repeated slashes collapse and the trailing slash is removed (empty becomes `/`).

3. **`ControllerClass`** → The PHP class that contains the logic for handling the request. It must be a global-namespace class stored as `<ControllerClass>.php` in `z_controllers`, because it is looked up by file name.

4. **`ControllerAction`** → The specific method inside the controller that should be executed. Any public method works, the `action_` prefix is not required. Instead of the pair, the action can be a closure whose parameters are resolved by type (`Request`, `Response`, or `array` for the route parameters), so 0 to 3 parameters work in any order; any other parameter gets its default value, else `null`. Static arguments are ignored for closures and `$req->getRouteParameter()` stays empty, use the `array` parameter.

---

### Example

```php
Route::get('/users/{id}', [UserController::class, 'show']);
```

This means:

When a client makes a `GET` request to `/users/42`,  
the router will call the `show()` method inside the `UserController` and make `{id}` (`42`) available as [**Route Parameter**](#route-parameters).

---

### Case and Spelling

Since version **1.3.0**, routes match regardless of case: `GET /Users/42` reaches the route above as well.
Route parameters keep the spelling of the request, so `{id}` of `/USERS/AbC` is `AbC`.

The pattern of a route parameter ignores case too, `/codes/{code:[A-Z]+}` also matches `/codes/abc`.

---

## Route Groups

Sometimes multiple routes share the same prefix (for example, `/api/v1/...`).
Instead of repeating the prefix in every route, you can define a **group**:

```php
Route::group('/api/{apiVersion}', function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});
```

### Explanation

* The group prefix (`/api/{apiVersion}`) will automatically be applied to all routes inside the group.
* In this example:

    1. `GET /api/{apiVersion}/users`  → `UserController::index`
    2. `POST /api/{apiVersion}/users` → `UserController::store`

---

### Nested Groups

You can also nest groups inside each other:

```php
Route::group('/api/{apiVersion}', function () {
    Route::group('/users', function () {
        Route::get('', [UserController::class, 'index']);      // GET /api/{apiVersion}/users
        Route::get('/{id}', [UserController::class, 'show']);  // GET /api/{apiVersion}/users/{id}
    });
});
```

The prefixes are concatenated in order, and the routes inherit the [middleware](#middleware-on-groups) of all enclosing groups.

This allows you to build **modular and structured route definitions** that are easy to maintain.

---

## Middleware and AfterMiddleware

ZubZet allows you to attach **middleware** and **afterMiddleware** to both routes and groups.

* **Middleware** → Executed **before** the route runs.  
  Example use cases: `authentication`, `user validation`, `request logging`.
* **AfterMiddleware** → Executed **after** the route has been processed; its return value is ignored, and it does not run if the action ends the script (`exit`, for example `generateRest()` or `rerouteUrl()`).  
  Example use cases: `response formatting`, `cleanup`, `analytics logging`.

### Syntax

```php
Route::get('/test', [TestController::class, 'route'])
    ->middleware([TestController::class, 'Route_Middleware_Accept'])
    ->afterMiddleware([TestController::class, 'Route_Afterware']);
```

* The middleware and afterMiddleware use the same format as routes:
  `[ControllerClass::class, ControllerAction]` arrays, never closures, and are called like actions with `($req, $res, ...$arguments)`.
* A route or group registers when its fluent chain is destroyed, so end the statement after the last call and do not store the result in a variable.

---

### Middleware Rules

- A middleware **must return `true`** in order for the request to continue.
- If a middleware returns anything other than `true`, the execution stops immediately:
    - No further middlewares are run
    - The route handler itself is **not executed**
    - No afterMiddleware is executed

Example flow with multiple middlewares:

1. **Authentication** → returns `true` ✅ → continue
2. **UserRegistry** → returns `true` ✅ → continue
3. **LoginLogging** → returns `true` ✅ → route runs

But if **Authentication** returns anything else (it sets no status code and renders nothing itself, so it has to produce its own response):

* Execution stops right there
* Neither `UserRegistry`, the route, nor any afterMiddleware will be executed

---

### Middleware on Groups

You can also attach middleware to entire groups.
All routes inside the group will inherit the defined middleware and afterMiddleware.

```php
Route::group('/api/{apiVersion}', function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
})
->middleware([AuthController::class, 'checkAuth'])
->afterMiddleware([LogController::class, 'logRequest']);
```

This means:

Every request to `/api/{apiVersion}/users` or `/api/{apiVersion}/users/{id}`:

  1. First passes through `checkAuth` `(middleware)`  
  2. Then executes the route  
  3. Finally runs `logRequest` `(afterMiddleware)`

With several groups, group middleware run from outer to inner before the route's own middleware; afterMiddleware run the route's own first, then the groups from inner to outer.

A group also guards the [controller-based actions](controllers-and-actions.md) below its prefix, so a group without routes is enough for them:

```php
Route::group('/admin')->middleware([AuthController::class, 'checkAuth']);
```

`checkAuth` runs before every action of the `AdminController`, in every spelling that reaches it: `/admin/users`, `/Admin/users` and `/%41dmin/users` alike. The prefix is compared the way the controller name is resolved: decoded, with `-` as `_`, `.` as `§2E` and `ä`, `ö`, `ü` as `ae`, `oe`, `ue`, regardless of case. A group `/admin-panel` therefore also covers `/admin_panel/...`. Direct `executePath()` calls (CLI `run`, `checkPermission()` redirects, error pages) skip explicit routes and group middleware. All matching groups run outer to inner, afterMiddleware in the same order, and a middleware returning anything but `true` ends the script with `exit`.

An empty prefix covers every URL. Otherwise a prefix covers a URL only if it equals the URL or is followed by `/` in it, and the whole prefix is compared literally against the path, which always starts with `/`. Three kinds of prefix therefore never guard a controller-based URL: one with a trailing slash (`/admin/`), one without a leading slash (`admin`) and one with a `{placeholder}` (`/api/{apiVersion}`). Placeholders are only resolved for routes, not for controller URLs. Nested groups concatenate their prefixes without a separator, so every nested prefix needs its own leading slash and none may end with one. A nested group stores only its own middleware; the parent matches the URL separately.

The groups for controller-based URLs are stored by their full prefix, spelled exactly as declared. Two groups with the same full prefix (`Route::group('/admin')` declared twice, or a nested `/admin` inside a group `/` that adds up to the same string) therefore do not add up: the later declaration replaces the earlier one, and only its `middleware`, `afterMiddleware` and `withoutCsrf()` guard the controller actions below that prefix. Merge them into one group instead. This only affects the controller fallback, the routes inside each group still run with the middlewares of every group around them. Prefixes that differ only in spelling (`/admin` and `/Admin`) are separate entries, so both apply to the same URL.

---

### CSRF Opt-out

The [CSRF check](csrf-protection.md) runs before the middlewares of a route. A route or group whose callers authenticate without the session cookie opts out with `withoutCsrf()`, which is inherited like a group middleware:

```php
Route::group('/api', function () {
    Route::post('/orders', [ApiController::class, 'action_orders']);
})->withoutCsrf();
```

See [When the check runs](csrf-protection.md#when-the-check-runs) for the requests the check already skips by itself.

---

## Route Parameters

Within the routing system, you can define dynamic route segments using curly braces, e.g. `{parameter}`.  
These placeholders are automatically treated as route parameters.

To access a parameter inside a controller action, you can use:

```php
$req->getRouteParameter("key");
```

If you call [`$req->getRouteParameter()`](../api/classes/ZubZet-Framework-Message-Request.html#method_getRouteParameter) without passing a key, it will return **all route parameters** as an array. A key that does not exist gives `null`. The parameters, including the placeholders of group prefixes, are set before every stage (action, middleware, afterMiddleware) and reset for the next one, so controller-based actions and their group middleware see an empty array. Values are URL-decoded before matching, so an encoded `%2F` can span segments.

### Example

```php
Route::get('/api/{apiVersion}', [ExampleController::class, 'Example']);
```

```php
class ExampleController extends z_controller {

  public function Example(Request $req, Response $res) {
    $version = $req->getRouteParameter("apiVersion");

    if ($version == "v1") {
        print_r("Correct Version v1");
    } else {
        print_r("Wrong Version");
    }
  }
}
```

---

## Static Arguments

Since version **1.2.0**, you can attach **static arguments** to a route, middleware, or
afterMiddleware directly in its definition. Unlike [route parameters](#route-parameters) (which come
from the URL), static arguments are fixed values baked into the route — handy for reusing one handler
with different configuration.

Pass an array as the **third argument**:

```php
Route::get('/reports/sales', [ReportController::class, 'render'], ['sales', 2026]);
```

The values are handed to the action **after** `Request` and `Response`:

```php
public function render(Request $req, Response $res, string $type, int $year) {
    // $type === 'sales', $year === 2026
}
```

Middlewares and afterMiddlewares accept static arguments the same way; each stage receives only its own list, and a string-keyed array is passed as named arguments:

```php
Route::get('/admin', [AdminController::class, 'index'], ['dashboard'])
    ->middleware([AuthController::class, 'requireRole'], ['admin'])
    ->afterMiddleware([AuditController::class, 'log'], ['admin-view']);
```