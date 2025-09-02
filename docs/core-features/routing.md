# Routing in ZubZet

Since version **1.0.0**, ZubZet not only includes the internal routing system ([controller-based actions](/docs/core-features/controllers-and-actions)) but also provides a route definition system.  
The routing definition system is based on [Slim](https://www.slimframework.com/).

---

## What does "Routing" mean?

Routing is the process of defining how your application responds to different HTTP requests.
When a client (e.g., a browser or API consumer) sends a request to your server, the router decides **which piece of code (controller and method)** should handle that request.
This makes it possible to build clean, structured, and maintainable applications where every endpoint has a clear definition.

---

## How to use the routing system?

To define your routes, you first need a `Routes` folder in your project’s **app directory**.
Inside this folder, you can create multiple files. Each file can contain routes, and all of them will be automatically registered by the framework.

---

## How to register a route?

You can register a route with the following syntax:

```php
Route::{method}({endpoint}, [{ControllerClass}::class, {ControllerAction}]);
```

### Explanation

1. **`method`** → One of the supported HTTP methods:

    - `any(endpoint, action)` → Match any HTTP method
    - `get(endpoint, action)` → Match `GET` requests
    - `post(endpoint, action)` → Match `POST` requests
    - `put(endpoint, action)` → Match `PUT` requests
    - `delete(endpoint, action)` → Match `DELETE` requests
    - `patch(endpoint, action)` → Match `PATCH` requests
    - `options(endpoint, action)` → Match `OPTIONS` requests
    - `define(method, endpoint, action)` → Define a custom method

2. **`endpoint`** → The URL path where the route will be accessible (e.g., `/users`, `/products/{id}`).  
   You can also define [**Route Parameter**](#route-parameters) using curly braces (e.g., `/users/{id}`), which will be passed into your controller method.

3. **`ControllerClass`** → The PHP class that contains the logic for handling the request.

4. **`ControllerAction`** → The specific method inside the controller that should be executed.

---

### Example

```php
Route::get('/users/{id}', [UserController::class, 'show']);
```

This means:

When a client makes a `GET` request to `/users/42`,  
the router will call the `show()` method inside the `UserController` and pass the `{id}` parameter (`42`) as [**Route Parameter**](#route-parameters) to it.

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

This allows you to build **modular and structured route definitions** that are easy to maintain.

---

## Middleware and AfterMiddleware

ZubZet allows you to attach **middleware** and **afterMiddleware** to both routes and groups.

* **Middleware** → Executed **before** the route runs.  
  Example use cases: `authentication`, `user validation`, `request logging`.
* **AfterMiddleware** → Executed **after** the route has been processed.  
  Example use cases: `response formatting`, `cleanup`, `analytics logging`.

### Syntax

```php
Route::get('/test', [TestController::class, 'route'])
    ->middleware([TestController::class, 'Route_Middleware_Accept'])
    ->afterMiddleware([TestController::class, 'Route_Afterware']);
```

* The middleware and afterMiddleware use the same format as routes:
  `[ControllerClass::class, ControllerAction]`.

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

But if **Authentication** returns anything else (e.g., an error response):

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

---

## Route Parameters

Within the routing system, you can define dynamic route segments using curly braces, e.g. `{parameter}`.  
These placeholders are automatically treated as route parameters.

To access a parameter inside a controller action, you can use:

```php
$req->getRouteParameter("key");
```

If you call `$req->getRouteParameter()` without passing a key, it will return **all route parameters** as an array.

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