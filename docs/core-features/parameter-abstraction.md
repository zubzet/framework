# Parameters, post, get, file and cookies
## URL Parameters
When using virtual links, parameters as “subfolders” is a great way of transfering values, that are also very readable. See the following link schema as an example of what is meant by parametres within the virtual link: `controller/action/param0/param1/param2/...`
### Read the parameters using code
To [read the virtual url parameters](../api/classes/ZubZet-Framework-Message-Request.html#method_getParameters) use `$req->getParameters`. It works using an offset and a length: length 1 returns the single segment (`false` when absent) or, with a value to compare against, a boolean from a loose `==`; any other length returns an array slice, `null` all remaining segments. `getReadableParameter()` splits an SEO segment such as `title-64` at the last dash into `text` and `id` (without a dash the whole segment is the `id`, without a segment both are empty strings), and `getUrlParts()` returns all raw segments below the root directory, not URL-decoded (a double slash gives an empty one). `getParameters()` always skips the first two segments (controller and action), so for routes of another shape use [`getRouteParameter()`](routing.md#route-parameters). `getCurrentURL()` returns `host` plus the raw `REQUEST_URI` with query string, `getRoot()` the `root` setting (host plus root directory, also `Z.Request.rootHost`) and `getRootFolder()` the `rootFolder` path such as `/`; `getDomain()` returns `host` without scheme, port and path, and `isCli()` is `true` for console runs.
### Examples
Example URL:<br>
`www.yourwebsite.com/{controller}/{action}/a/b/c`

Get the first parameter:<br>
`$req->getParameters(0, 1);`<br>
Returns: "a"

Get all the parameters after the second one:<br>
`$req->getParameters(1);`<br>
Returns: ["b", "c"]

Check if the first parameter is "test":<br>
`if($req->getParameters(0, 1, "test")) {` ...<br>
Returns: false

Get the first parameter when using a fallback action<br>
`$req->getParameters(-1, 1);`<br>
Returns "action"


### Code Example
```php
// Example execution `{root}/{controller}/employee/view/2`
public function action_employee(Request $req, Response $res) {

    // Checks if the first URL parameter (offset 0, length 1) is "view"
    if ($req->getParameters(0, 1, "view")) {

        // Retrieves the second URL parameter (offset 1, length 1), which is the employeeId
        $employeeId = $req->getParameters(1, 1);

        return $res->render("employee/employee_view", [
            "employee" => $req->getModel("Employee")->getEmployeeById($employeeId)
        ]);
    }

    return $res->render("employee/list");
}
```

## GET, POST, COOKIES, FILE as a method
The framework includes some extra functionality when it comes to the above mentioned features and you should use the framework instead of the traditional way. This is because the framework employes extra filtering and processing methods as well as [error handling](error-handling.md) already for you.

### Getting POST and GET parameters
`$req->getGet` and `$req->getPost` are [methods to get form parameters](../api/classes/ZubZet-Framework-Message-Input-CanRetrieveFromInput.html). These also enable to set default parameters, returned when the key is unset or null; without a key they return the whole array.<br>
**Note:** Post parameters get decoded automatically if their values have the special prefix `<#decURI#>`. This decoding is applied recursively to nested values, replaces the value in `$_POST` itself and allows to transmit special characters (Z.js sends its fields that way).

### Cookies
`$req->getCookie` gets a cookie of the request; `$res->unsetCookie` removes it there, `$res->setCookie` does not add it before the next request. It has a second parameter to set a default if the cookie is not set.

`$res->setCookie` has the [same parameters](https://www.php.net/manual/en/function.setcookie.php) as the native `setcookie` function of php. It should be used, because in the future may more logic build into the framework that deals with cookies.

**Note:** `setCookie` is a plain pass-through (`setcookie(...func_get_args())`) and applies **no security defaults**. `Secure`, `HttpOnly` and `SameSite` are only set if you pass them, so a bare `$res->setCookie("name", "value")` is sent without any of the three and is readable by JavaScript and sent over plain HTTP. Use the options-array form and set them yourself:

```php
$res->setCookie("name", "value", [
    "expires" => time() + 3600,
    "path" => "/",
    "secure" => $res->cookieSecure(),
    "httponly" => true,
    "samesite" => "Lax",
]);
```

`$res->cookieSecure()` is `true` when the `host` setting starts with `https://`. The framework's own cookies pass these attributes explicitly through an internal helper, see [CSRF protection](csrf-protection.md).

`$res->unsetCookie($name, $path = "/", $domainScope = "")` removes the cookie from the request input and sends an expired one (empty value, timestamp 1). The path and domain must match those it was set with; `$res->getCookieDomainScope()` returns `.<domain>` when `login_scope_allow_subdomains` is the string `true`, otherwise an empty string (host-only).

### File
`$req->getFile` uses `$_FILE` like `$req->getPost` uses `$_POST`. `getFiles()` and `getCookies()` are the key-less forms of `getFile()` and `getCookie()`, and `getFile($key, $default)` returns `$default` for a missing key like the other getters.

### Body and raw input
`$req->getBody()` returns the raw request body, or `null` when it is empty or exactly `"0"`. `$req->getJson()` decodes it into associative arrays, see [JSON body](#json-body). `$req->store` is a public array that the framework never fills and `reroute()` replaces the `Request`, so it is empty afterwards. `SERVER`, `SESSION`, `REQUEST` and `body` have no getters, read them from `$req->input`; there is no `getMethod()`, `getHeader()`, `isPost()` or `isAjax()`, use `$req->input->SERVER["REQUEST_METHOD"]` or the `HTTP_*` keys. `$req->ip()` returns the first set of `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR` and `REMOTE_ADDR` (then the `getenv()` variants), `referer()` and `userAgent()` the header or `null`, and `getExecutionTime()` the seconds since `REQUEST_TIME_FLOAT` (`null` if missing).

### Accept-Language
`$req->acceptLanguage()` returns the locales of the request's `Accept-Language` header, best first. Entries are ordered by their `q` value, malformed entries and `q=0` are dropped, and each tag is brought into its canonical case, so `en-us` and `EN-US` are one entry. The header is client controlled, so only the first 50 entries are read.

```php
$req->acceptLanguage(); // "de-CH,de;q=0.9,en;q=0.8" -> ["de-CH", "de", "en"]
```

[Translations](translations.md#locale) use it to pick the locale of a visitor without one of their own.

### Bearer token
`$req->bearerToken()` returns the token of an `Authorization: Bearer <token>` header, or `null` when the header is missing, uses another scheme or does not carry exactly one token (`Bearer `, `Bearer a b`). The framework [authenticates by it](access-control.md#bearer-authentication) before the login cookie.

### JSON body
`$req->getBody()` returns the raw request body. `$req->getJson()` decodes it as JSON, objects into associative arrays, and throws otherwise. An `UnexpectedValueException` carries the HTTP status to answer with as its code:

| Status | Reason |
|--------|--------|
| 405 | The method is not POST, PUT, PATCH or DELETE |
| 415 | The Content-Type is not exactly `application/json`, or names a charset other than utf-8 |
| 413 | The body is longer than `$maxBytes` (default 1 MiB) |
| 422 | The body is well formed JSON, but neither an object nor a list (`42`, `"text"`, `true`, `null`) |

A body that is not well formed JSON (a BOM, an empty body, invalid UTF-8 or nesting deeper than `$maxDepth`, default 32) throws a `\JsonException` instead; answer it with 400.

The strict Content-Type is what keeps a cross site form out: a form can send `text/plain` and form types, but never `application/json`. Integers beyond `PHP_INT_MAX` come back as strings instead of lossy floats.

```php
try {
    $data = $req->getJson();
} catch(\UnexpectedValueException $e) {
    http_response_code($e->getCode());
    return $res->json(["error" => $e->getMessage()]);
} catch(\JsonException $e) {
    http_response_code(400);
    return $res->json(["error" => $e->getMessage()]);
}
```

## See also

- [Aliases and virtual links](../advanced-features/aliases-and-virtual-links.md): shorter or alternative versions of your virtual links
- [File uploads](../forms/file-uploads.md): validating and processing files received via `$req->getFile`
