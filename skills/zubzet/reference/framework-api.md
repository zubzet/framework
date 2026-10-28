# Framework API: where to look it up

This file is a map, not a reference. The first table takes a question to its docs page (relative to `vendor/zubzet/framework/docs/`); the tables after it name, per capability, the source file to grep when the docs stop (relative to `vendor/zubzet/framework/src/`) and just enough names to recognise that the capability exists. Signatures, parameters and return values are read from the docs page or the source, never from memory.

## Question to docs page

The quickest route when you know what you are asking. Paths are relative to the docs root `vendor/zubzet/framework/docs/` (`docs/` inside the framework repository); `mkdocs.yml` is the table of contents.

| Question | Page |
| --- | --- |
| Global helpers (`model()`, `config()`, `user()`, `logger()` ...) | `core-features/global-helper-functions.md` |
| Request input, URL parameters, cookies, files | `core-features/parameter-abstraction.md` |
| Answering JavaScript (`success`, `error`, `json`) | `core-features/rest-api.md` |
| Controllers, actions, `action_index`, `action_fallback` | `core-features/controllers-and-actions.md` |
| Route files, groups, middleware, route parameters | `core-features/routing.md` |
| Models, `exec()`, bind types, result helpers | `core-features/models.md` |
| Query builder | `core-features/query-builder/index.md`, `query-builder/examples.md` |
| Migrations, seeds, `db:*` commands, production rollout | `core-features/migrations/index.md`, `migrations/examples.md` |
| Z.Forms fields and validation rules | `forms/auto-form-validation.md` |
| CED (n child rows of one record) | `forms/ced-validation.md` |
| File uploads | `forms/file-uploads.md` |
| `Z.Request.action` from JavaScript | `frontend-integration/backend-requests.md` |
| Login and signup presets (`Z.Presets`) | `frontend-integration/presets.md` |
| Views | `core-features/views.md` |
| Layouts and default layout resolution | `core-features/layouts.md` |
| Permissions, `checkPermission`, wildcards | `core-features/permission-system.md` |
| `User`, `Role`, `Organization`, `Session` objects | `core-features/access-control.md` |
| Passwords | `core-features/password-handling.md` |
| Logging channels and levels | `core-features/logging.md` |
| Error handling, Whoops, debug bar | `core-features/error-handling.md`, `core-features/debug-bar.md` |
| Console commands, running an action from the CLI | `core-features/console-commands.md`, `advanced-features/commands.md` |
| URL aliases | `advanced-features/aliases-and-virtual-links.md` |
| Asset proxy for bundled libraries | `core-features/asset-proxy.md` |
| Configuration and `CONFIG_*` overrides | `core-features/configuration.md`, `setup/installation.md` |
| Email | `template-rendering-usages/sending-an-email.md`, `guides/email.md` |
| Maintenance mode | `core-features/maintenance.md` |
| Admin panel, login as another user | `z-admin/usage.md`, `z-admin/login-as-another-user.md` |

## Global helpers

Documented in `core-features/global-helper-functions.md` (source: `Support/GlobalReferences.php`, `Support/Helpers.php`).

| Helper | Use it for | Instead of |
| --- | --- | --- |
| `model("Name")` | Loading a model anywhere | `$req->getModel()`, `new SomethingModel()`, passing models around |
| `config("key")` | Any setting, including in model constructors | `$booter->req->getBooterSettings()`, `getenv()`, constants, hardcoded values |
| `user()` | The logged in user | `$req->getRequestingUser()`, passing `$userId` through three layers |
| `request()` | The request outside an action signature | `$_SERVER`, `$_GET`, globals |
| `response()` | Mail or reroute from shared code | `header()`, building mails by hand |
| `logger()`, `logger("channel")` | Structured logging with context | `error_log`, `file_put_contents`, `echo` in CLI code |
| `view("path", $opt)` | Rendering outside an action | Including PHP files, string templates |
| `db()` | Raw connection in migrations and CLI | A second mysqli connection, PDO |
| `isCli()` | Web versus `php index.php` branch | Scattered `php_sapi_name()` checks |
| `e($value)` | Escaping a value that meets HTML | `htmlspecialchars` with hand picked flags |
| `makeSlug($str)` | URL and `data-test` slugs | Custom regex replacements |
| `shortenStr($str, $max)` | Truncating with an ellipsis | `substr` plus `...` |
| `emptyToNull($value)` | `""` / `"null"` to `null` before binding | Ternaries on every field |
| `de_strtolower($str)` | Lowercasing German text | `mb_strtolower` with manual replacements |

Constants (`src/Core/Constants.php`): `FILE_SIZE_1MB` ... `FILE_SIZE_100GB` for upload limits, `TIMESPAN_DAY_1` ... `TIMESPAN_DAY_365` for date arithmetic, `UPLOAD_SUCCESS` and `UPLOAD_ERROR_*` for upload results. Never write `86400` or `10485760` yourself.

## Request and Response

| Need | Source | Names to recognise |
| --- | --- | --- |
| Input: POST, GET, files, cookies, URL parameters | `Message/Request.php` | `getPost`, `getGet`, `getFile`, `getCookie`, `getParameters` |
| Branching on what the page sent | `Message/Request.php` | `isAction`, `hasFormData` |
| Permission gate | `Message/Request.php` | `checkPermission`, `checkSuperPermission` |
| Form validation | `Form/Validation/Field.php`, `Form/Validation/CanValidateForm.php` | `validateForm`, `validateCED`, `FormField` |
| Persisting a validated form | `Message/Response.php` | `insertDatabase`, `updateDatabase`, `insertOrUpdateDatabase`, `doCED` |
| Answering JavaScript | `Message/Response.php` | `success`, `error`, `formErrors`, `json` |
| Redirects and aliases | `Message/Response.php` | `reroute`, `rerouteUrl` |
| Rendering and layouts | `Rendering/CanRenderView.php`, `Rendering/HandlesDefaultLayout.php` | `render`, `setDefaultLayout`, `setGlobalDefaultLayout` |
| Mail | `Message/Response.php` | `sendEmail`, `sendEmailToUser` |
| Framework upload handler (disk based) | `Form/Upload.php` | `upload` |
| Sessions, login as | `Message/Response.php` | `loginAs`, `logout` |
| Cookies | `Message/Response.php` | `setCookie`, `unsetCookie` |
| Request metadata | `Message/Request.php` | `ip`, `userAgent`, `referer`, `getRoot`, `getUrlParts`, `isCli` |

## Controller and model base classes

| Need | Source | Names to recognise |
| --- | --- | --- |
| Select options and CED values for Z.Forms | `Core/Controller.php` | `makeFood`, `makeCEDFood` |
| Running SQL and reading results | `Core/Model.php`, `Database/Connection.php` | `exec`, `resultToLine`, `resultToArray`, `getInsertId`, `countResults` |
| Dynamic SQL | `QueryBuilder/CanBuildQuery.php` | `dbSelect`, `dbInsert`, `dbUpdate`, `dbDelete`, `getQueryBuilder` |
| Existence and uniqueness checks | `Database/Interaction.php` | `checkIfExists`, `checkIfUnique` |
| Hiding infrastructure queries from the debug bar | `Database/IsInternalModel.php` | `IsInternalModel` |

## Authentication objects

| Need | Source | Names to recognise |
| --- | --- | --- |
| Users, roles, permissions, organizations, sessions | `Authentication/Permission/` (`User`, `Role`, `Group`), `Authentication/` (`Organization`, `Session`; `User.php` there is the current user object) | `byId`, `byEmail`, `add`, `remove`, `rolesAdd`, `permissionsAdd`, `hasAccessAll` |
| Passwords | `Authentication/PasswordHash/` | `User::add`, `updatePassword`, `verifyPassword`, `Password::hash`, `Password::verify` |
| The current user in views and code | `Authentication/User.php` | `user()`, `$opt["user"]`, `userId`, `isLoggedIn`, `checkPermission` |

Use these for anything touching `z_user`, `z_role`, `z_logintoken`, `z_organization`. Never query those tables directly in a project model, except in seeds.

## Z.js and the CLI

| Need | Source | Names to recognise |
| --- | --- | --- |
| Forms and buttons in the browser | `web/Z.js` (package root) | `Z.Forms`, `Z.Request.action`, `Z.Lang` |
| Login and signup pages | `web/Z.js` | `Z.Presets.Login`, `Z.Presets.Signup` |
| Console | `Console/` | `php index.php run`, `db:migrate`, `db:seed`, `db:sync`, `db:status`, `info:startup` |
| Logging | `Logger/` | `logger()`, `LoggerFactory::register` |
| Asset proxy | `Resources/AssetProxy.php` | `_zubzet/asset-proxy/...`, `registerWebRootSource` |
| Maintenance | `Maintenance/` | `maintenance_mode`, `app/Views/maintenance.html` |

## Models reading their settings

A model constructor reads its settings with `config()`, a model gets other models with `$this->getModel()` or `model()`, and a model knows who is acting through `user()`. None of these need to be passed in as arguments.

**The fallback of `config()` is a named argument**: `config("page_size", default: 25)`. The second positional parameter is `$useDefault`, so `config("page_size", 25)` ignores the 25 silently.

```php
public function __construct($z_db, z_framework $booter) {
    parent::__construct($z_db, $booter);

    $this->bucket = (string) config("s3_bucket");
    $this->client = new S3Client([
        "accessKeyId" => (string) config("s3_access_key"),
        "accessKeySecret" => (string) config("s3_secret_key"),
        "region" => (string) config("s3_region"),
        "endpoint" => config("s3_schema") . "://" . config("s3_endpoint"),
        "pathStyleEndpoint" => true,
    ]);
}
```
