### Global Helper Functions

To optimize developer experience and eliminate redundant boilerplate, **global helper functions** have finally been integrated. These helpers offer immediate and consistent access to core framework components and services. All previously available methods remain **fully supported** to ensure seamless backward compatibility. The functions below throw a `RuntimeException` at boot when a function of the same name already exists (except `__()`, which yields to an existing one).

---

### `zubzet()`

Returns the [main instance](../api/classes/ZubZet-Framework-ZubZet.html) of the ZubZet framework, or throws a `NotInstantiatedException` before it exists.

**Syntax:** `zubzet()`

---

### `model()`

Provides direct access to a specific [model](models.md) instance.

**Syntax:** `model($modelName, $dir = null)`

* **$modelName**: The name of the model to be instantiated.
* **$dir**: Optional directory to load the model from instead of the usual lookup.

**Note:** In controller actions, `$reqObj->getModel($modelName)` remains a supported alternative.

**Example:**

```php
model("Test")->onTest();

```

---

### `request()`

Accesses the instance of the [current request](../api/classes/ZubZet-Framework-Message-Request.html), or throws a `NotInstantiatedException` when there is none. It works on a snapshot of the superglobals taken at boot, so later writes to `$_GET`, `$_POST`, `$_COOKIE` or `$_SESSION` are invisible to it.

**Syntax:** `request()`

**Example:**

```php
request()->getPost("POST_PARAMETER");

```

---

### `response()`

Accesses the instance of the [current response](../api/classes/ZubZet-Framework-Message-Response.html), the same one that controller constructors, middleware and actions receive.

**Syntax:** `response()`

**Example:**

```php
response()->sendEMail($to, $subject, $document);

```

---

### `config()`

Retrieves a [configuration value](configuration.md) by its key.

**Syntax:** `config(?string $key = null, bool $useDefault = true, mixed $default = null)`

* **$key**: The configuration identifier. If it is empty, the complete settings array is returned, credentials included.
* **$useDefault**: Whether to return a fallback value if the key is not found. Defaults to `true`; with `false`, a missing key throws an `InvalidArgumentException`.
* **$default**: The value to return if `$useDefault` is true and the key is missing. Defaults to `null`.

**Note:** `$useDefault` is the second positional argument, so `config("key", 5)` does not set a default of `5`. Pass the fallback by name: `config("key", default: 5)`.

**Note:** Previously accessible via `$req->getBooterSettings(...)` inside controller actions; this approach remains fully supported.

**Example:**

```php
config("db_username");

```

---

### `configNumeric()`

Retrieves a [configuration value](configuration.md) that has to be a number.

**Syntax:** `configNumeric(string $key, int $default)`

* **$key**: The configuration identifier.
* **$default**: The value to return if the key is missing.

**Note:** Non-numeric values are rejected with an `InvalidArgumentException` instead of being cast. A typo or a value like `off` would otherwise silently become `0` and disable whatever the setting controls.

**Example:**

```php
configNumeric("db_max_retries", 3);

```

---

### `user()`

Returns the [request user](../api/classes/ZubZet-Framework-Authentication-User.html), built once per request when the framework is constructed, from the bearer token or the `z_login_token` cookie. It is also available as `$req->getRequestingUser()`, `$req->booter->user`, `$opt["user"]` in views and as the global alias `User`, which is not the record class `Authentication\Permission\User` (import that one with `use`).

**Syntax:** `user()`

**Note:** This streamlines the previous method of calling `$req->getRequestingUser()` within controller actions. Check `user()->isLoggedIn` (`true` only when token, session and an active `z_user` row all resolve) to detect anonymous visitors. `userId` is the account the session acts as (`null` when anonymous), `orgId` the user's organization id (`null` when anonymous or without one), `getSessionToken()` the current session token (`null` when anonymous), `execUserId` the real user behind a [login as](../z-admin/login-as-another-user.md) session (equal to `userId` for a normal login) and `fields` the complete `z_user` row including `password`, `salt`, `password_scheme` and custom columns (empty when anonymous).

**Example:**

```php
user()->userId;

```

---

### `db()`

Provides the [database instance](../api/classes/ZubZet-Framework-Database-Connection.html) for a specified connection, or throws a `NotInstantiatedException` before it exists.

**Syntax:** `db(string $connection = "default", bool $allowUnsetConnection = false)`

* **$connection**: The name of the database connection (defaults to "default", any other name throws an `InvalidArgumentException`).
* **$allowUnsetConnection**: Return `null` instead of throwing while the connection does not exist yet (early in boot).

**Note:** Database access is no longer restricted to using `$this->z_db` within models; `db()`, `zubzet()->z_db` and `$this->z_db` are the same shared connection.

**Example:**

```php
db()->exec("SELECT * FROM z_user");

```

---

### `logger()`

Returns a Monolog logger instance for the given channel, or throws a `NotInstantiatedException` before the framework instance exists.

**Syntax:** `logger(string $name = null)`

* **$name**: The channel name. Defaults to `"app"` if omitted.

Loggers are created on first use and cached for subsequent calls with the same name. See [Logging](logging.md) for full configuration details.

**Example:**

```php
logger()->info("Something happened");
logger("payments")->warning("Timeout", ["orderId" => $id]);

```

---

### `view()`

Renders a [view template](views.md) directly.

**Syntax:** `view(string $document, array $opt = [], array|string $options = [])`

* **$document**: The path to the view file.
* **$opt**: Data array passed to the view.
* **$options**: Additional rendering options, or a layout name as a string.

**Note:** This serves as a streamlined alternative to rendering via the response object using `$resObj->render(...)`.

**Example:**

```php
view("adminpanel/dashboard");

```

### `encryptSecret()`

Encrypts a secret with the `encryption_key` setting, for example before storing it in the database. See [Secrets](secrets.md).

**Syntax:** `encryptSecret(string $plaintext): string`

* **$plaintext**: The value to encrypt.

**Example:**

```php
$stored = encryptSecret($secretKey);
```

### `decryptSecret()`

Decrypts a value created by `encryptSecret()`. Throws a `ZubZet\Framework\Security\Secrets\DecryptionException` when the key is wrong or the value is malformed or was modified. See [Secrets](secrets.md).

**Syntax:** `decryptSecret(string $encrypted): string`

* **$encrypted**: A value returned by `encryptSecret()`.

**Example:**

```php
$secretKey = decryptSecret($stored);
```

### `__()`

Translates a message id into the active locale. See [Translations](translations.md) for the catalogues and how the locale is picked.

**Syntax:** `__(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string`

* **$id**: The message id. An id no catalogue defines is returned unchanged.
* **$parameters**: Placeholder values, keyed the way the catalogue spells the placeholder.
* **$domain**: The catalogue domain, `messages` when omitted.
* **$locale**: A locale override, the active locale when omitted.

The framework only declares it when no global `__()` exists yet, so an application or library that brings its own keeps it.

**Example:**

```php
echo __("dashboard.welcome", ["{name}" => $name]);
```

### `e()`

Escapes a string for safe HTML output. It strips tags (everything between `<` and `>`, so `1<2 and 3>2` becomes `12`) and then escapes the remaining special characters through the render engine, so its output matches what `{{ }}` produces in a [view](views.md).

**Syntax:** `e(?string $value): ?string`

* **$value**: The string to escape. `null` is passed through unchanged, so optional values can be escaped without an extra check.

Use it whenever a value ends up in HTML outside of a template echo, for example in attributes built in PHP.

`shortenStr($str, $maxlength = 10)` cuts at word boundaries and appends `...`, but returns a string that fits with a trailing space; `getCaller($depth = 1)` only works for `0` and `1`; `var_swap(&$x, &$y)` swaps two variables. `makeSlug()` drops every character outside `A-Za-z0-9-_` and space without transliterating, so `Größe` becomes `gre`; `emptyToNull(&$value)` also nulls `0`, `'0'`, `false`, `[]` and the string `null`.

**Example:**

```php
<a title="<?= e($opt["title"]) ?>">
```