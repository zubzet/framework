### Global Helper Functions

To improve developer experience and reduce boilerplate code, **global helper functions** have been introduced.
These helpers provide quick and consistent access to commonly used framework components and services.

All previously available alternatives remain **fully supported** to ensure backward compatibility.


### zubzet()

Returns the main instance of the ZubZet framework.


### model(string $modelName)

Provides access to a specific model instance.

In controller actions, `$reqObj->getModel($modelName)` remains a supported alternative.

**Example:**

```php
model("Test")->onTest();
```


### request()

Returns the instance of the **current request**.

**Example:**

```php
request()->getPost("POST_PARAMETER");
```


### response()

Returns the instance of the **current response**.

**Example:**

```php
response()->sendEMail($to, $subject, $document);
```


### config(string $key, bool $useDefault = false, mixed $default = null)

Returns a configuration value by key.

Previously, this was accessible via
`$req->getBooterSettings($key, $useDefault, $default)` inside controller actions.
This approach is still fully supported.

**Example:**

```php
config("db_username");
```


### user()

Returns the instance of the **currently authenticated user**.

Previously accessible via
`$req->getRequestingUser()` inside controller actions.
This approach remains supported.

**Example:**

```php
user()->userId;
```


### db(string $connection = "default")

Returns the database instance for the specified connection.

Previously, database access was only available within models via `$this->z_db`.
This method remains supported.

**Example:**

```php
db()->exec("SELECT * FROM z_user");
```


### view(string $document, array $opt = [], array $options = [])

Renders a view template.

Previously rendered via the response object using `$resObj->render(...)`.
This approach remains supported.

**Example:**

```php
view("adminpanel/dashboard");
```