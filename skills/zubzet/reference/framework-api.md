# Framework API: where to look it up

This file is a map, not a reference. Each row names the capability, the docs page that describes it (relative to `vendor/zubzet/framework/docs/`), the source file to grep when the docs stop (relative to `vendor/zubzet/framework/src/`), and just enough names to recognise that the capability exists. Signatures, parameters and return values are read from the docs page or the source, never from memory.

## Request and Response

| Need | Docs page | Source | Names to recognise |
| --- | --- | --- | --- |
| Input: POST, GET, files, cookies, URL parameters | `core-features/parameter-abstraction.md` | `Message/Request.php` | `getPost`, `getGet`, `getFile`, `getCookie`, `getParameters` |
| Branching on what the page sent | `forms/auto-form-validation.md`, `frontend-integration/backend-requests.md` | `Message/Request.php` | `isAction`, `hasFormData` |
| Permission gate | `core-features/permission-system.md` | `Message/Request.php` | `checkPermission`, `checkSuperPermission` |
| Form validation | `forms/auto-form-validation.md`, `forms/ced-validation.md` | `Form/Validation/Field.php`, `Form/Validation/CanValidateForm.php` | `validateForm`, `validateCED`, `FormField` |
| Persisting a validated form | `forms/auto-form-validation.md` | `Message/Response.php` | `insertDatabase`, `updateDatabase`, `insertOrUpdateDatabase`, `doCED` |
| Answering JavaScript | `core-features/rest-api.md` | `Message/Response.php` | `success`, `error`, `formErrors`, `json` |
| Redirects and aliases | `advanced-features/aliases-and-virtual-links.md` | `Message/Response.php` | `reroute`, `rerouteUrl` |
| Rendering and layouts | `core-features/views.md`, `core-features/layouts.md` | `Rendering/CanRenderView.php`, `Rendering/Resolver/DefaultLayout.php` | `render`, `setDefaultLayout`, `setGlobalDefaultLayout` |
| Mail | `template-rendering-usages/sending-an-email.md`, `guides/email.md` | `Message/Response.php` | `sendEmail`, `sendEmailToUser` |
| Framework upload handler (disk based) | `forms/file-uploads.md` | `Form/Upload.php` | `upload` |
| Sessions, login as | `z-admin/login-as-another-user.md` | `Message/Response.php` | `loginAs`, `logout` |
| Cookies | `core-features/parameter-abstraction.md` | `Message/Response.php` | `setCookie`, `unsetCookie` |
| Request metadata | `core-features/parameter-abstraction.md` | `Message/Request.php` | `ip`, `userAgent`, `referer`, `getRoot`, `getUrlParts`, `isCli` |

## Controller and model base classes

| Need | Docs page | Source | Names to recognise |
| --- | --- | --- | --- |
| Select options and CED values for Z.Forms | `forms/auto-form-validation.md`, `forms/ced-validation.md` | `Core/Controller.php` | `makeFood`, `makeCEDFood` |
| Running SQL and reading results | `core-features/models.md` | `Core/Model.php`, `Database/Connection.php` | `exec`, `resultToLine`, `resultToArray`, `getInsertId`, `countResults` |
| Dynamic SQL | `core-features/query-builder/index.md`, `query-builder/examples.md` | `QueryBuilder/CanBuildQuery.php` | `dbSelect`, `dbInsert`, `dbUpdate`, `dbDelete`, `getQueryBuilder` |
| Existence and uniqueness checks | `forms/auto-form-validation.md` | `Database/Interaction.php` | `checkIfExists`, `checkIfUnique` |
| Hiding infrastructure queries from the debug bar | `core-features/debug-bar.md` | `Database/IsInternalModel.php` | `IsInternalModel` |

## Authentication objects

| Need | Docs page | Source | Names to recognise |
| --- | --- | --- | --- |
| Users, roles, permissions, organizations, sessions | `core-features/access-control.md` | `Authentication/Permission/` (`User`, `Role`, `Group`), `Authentication/` (`Organization`, `Session`; `User.php` there is the current user object) | `byId`, `byEmail`, `add`, `remove`, `rolesAdd`, `permissionsAdd`, `hasAccessAll` |
| Passwords | `core-features/password-handling.md` | `Authentication/PasswordHash/` | `User::add`, `updatePassword`, `verifyPassword`, `Password::hash`, `Password::verify` |
| The current user in views and code | `core-features/permission-system.md` | `Authentication/User.php` | `user()`, `$opt["user"]`, `userId`, `isLoggedIn`, `checkPermission` |

Use these for anything touching `z_user`, `z_role`, `z_logintoken`, `z_organization`. Never query those tables directly in a project model, except in seeds.

## Blade, Z.js and the CLI

| Need | Docs page | Source | Names to recognise |
| --- | --- | --- | --- |
| Blade directives and components | `core-features/views.md` | `Rendering/Katana/` | `@auth`, `@guest`, `@checked`, `@selected`, `@disabled`, `@class`, `@forelse`, `$loop`, `@include`, `<x-component>`, `<x-zubzet::head>`, `<x-zubzet::body>` |
| Forms and buttons in the browser | `forms/auto-form-validation.md`, `frontend-integration/backend-requests.md` | `web/Z.js` (package root) | `Z.Forms`, `Z.Request.action`, `Z.Lang` |
| Login and signup pages | `frontend-integration/presets.md` | `web/Z.js` | `Z.Presets.Login`, `Z.Presets.Signup` |
| Console | `core-features/console-commands.md`, `advanced-features/commands.md` | `Console/` | `php zubzet run`, `db:migrate`, `db:seed`, `db:sync`, `db:status`, `info:startup` |
| Logging | `core-features/logging.md` | `Logger/` | `logger()`, `LoggerFactory::register` |
| Asset proxy | `core-features/asset-proxy.md` | `Resources/AssetProxy.php` | `_zubzet/asset-proxy/...`, `registerWebRootSource` |
| Health, maintenance | `core-features/health-endpoint.md`, `core-features/maintenance.md` | `IncludedComponents/routes/DefaultRoutes.php`, `Maintenance/` | `/_zubzet/health`, `maintenance_mode`, `app/Views/maintenance.html` |

The experimental `<x-zubzet::island>`, `<x-zubzet::modal>`, `<x-zubzet::action>` components exist; use them only when the project already does or the user asks.

## Models reading their settings

A model constructor reads its settings with `config()`, a model gets other models with `$this->getModel()` or `model()`, and a model knows who is acting through `user()`. None of these need to be passed in as arguments.

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
