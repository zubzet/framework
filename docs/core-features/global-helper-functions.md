### Global Helper Functions

To optimize developer experience and eliminate redundant boilerplate, **global helper functions** have finally been integrated. These helpers offer immediate and consistent access to core framework components and services. All previously available methods remain **fully supported** to ensure seamless backward compatibility.

---

### `zubzet()`

Returns the main instance of the ZubZet framework.

**Syntax:** `zubzet()`

---

### `model()`

Provides direct access to a specific model instance.

**Syntax:** `model(string $modelName)`

* **$modelName**: The name of the model to be instantiated.

**Note:** In controller actions, `$reqObj->getModel($modelName)` remains a supported alternative.

**Example:**

```php
model("Test")->onTest();

```

---

### `request()`

Accesses the instance of the current request.

**Syntax:** `request()`

**Example:**

```php
request()->getPost("POST_PARAMETER");

```

---

### `response()`

Accesses the instance of the current response.

**Syntax:** `response()`

**Example:**

```php
response()->sendEMail($to, $subject, $document);

```

---

### `config()`

Retrieves a configuration value by its key.

**Syntax:** `config(string $key, bool $useDefault = false, mixed $default = null)`

* **$key**: The configuration identifier.
* **$useDefault**: Whether to return a fallback value if the key is not found.
* **$default**: The value to return if `$useDefault` is true and the key is missing.

**Note:** Previously accessible via `$req->getBooterSettings(...)` inside controller actions; this approach remains fully supported.

**Example:**

```php
config("db_username");

```

---

### `user()`

Returns the instance of the currently authenticated user.

**Syntax:** `user()`

**Note:** This streamlines the previous method of calling `$req->getRequestingUser()` within controller actions.

**Example:**

```php
user()->userId;

```

---

### `db()`

Provides the database instance for a specified connection.

**Syntax:** `db(string $connection = "default")`

* **$connection**: The name of the database connection (defaults to "default").

**Note:** Database access is no longer restricted to using `$this->z_db` within models.

**Example:**

```php
db()->exec("SELECT * FROM z_user");

```

---

### `view()`

Renders a view template directly.

**Syntax:** `view(string $document, array $opt = [], array $options = [])`

* **$document**: The path to the view file.
* **$opt**: Data array passed to the view.
* **$options**: Additional rendering options.

**Note:** This serves as a streamlined alternative to rendering via the response object using `$resObj->render(...)`.

**Example:**

```php
view("adminpanel/dashboard");

```