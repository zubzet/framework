# Controllers

Framework docs: `vendor/zubzet/framework/docs/core-features/controllers-and-actions.md`, `routing.md`, `rest-api.md`.

## Shape of an action

Every action follows the same order. It is well tested and proven, so deviate from it only with a very good reason. The full example, written in the house style, opens [code-style/php.md](../code-style/php.md); the steps are:

1. **Permission**, always the first statement.
2. **Load** what every branch needs; reroute when the record does not exist or does not belong to this user.
3. **Async branches** (`isAction`), each one returns; a write checks its own finer permission first.
4. **Form branch** (`hasFormData`): validate, persist, return.
5. **Render**, the last statement.

## Rules

- **Permissions are checked carefully, rather more than less.** `$req->checkPermission("...")` is the first statement of every action that needs a login, including actions that only answer AJAX; a destructive or writing `isAction` branch checks its own finer permission after it (`shop.view` at the top, `shop.delete` inside `isAction("delete-product")`, as the guides do: `docs/guides/shop.md`, `docs/guides/todo.md`).
- `checkPermission` sends anonymous users to the login page and logged in users without the permission to the 403 page (`docs/core-features/permission-system.md`); never render a custom "forbidden" view. The boolean form `checkPermission("support", true)` is for deciding what to do: branch between two views for two roles, pass a `$canViewAny` flag to a model, hide a part of the page; the ordering is then naturally different, what matters is that every access path is covered. `checkSuperPermission` additionally accepts the permission of the real user behind a login-as session, so it is used only for support tooling that must stay reachable while a supporter is logged in as a customer. A public page has no check, and says so in a comment.
- Record ownership is not checked in the controller. The model query carries `WHERE userId = ?`. The controller only handles "not found" by rerouting.
- One `isAction` block per button type, each ending in `return $res->success([...])`. Order the blocks: reads first, then writes, then the form, then render.
- A controller passes data to the view as a flat associative array with descriptive keys. It may group or split a list for the view, build filter option arrays, and merge two model results. It does not loop over rows to compute business values.
- Use the global helpers: `model("Name")`, `user()->userId`, `config("key")`, `logger()`. `$req->getModel()` and `$req->getRequestingUser()` still work but new code uses the helpers everywhere.
- URL parameters: `$req->getParameters(0, 1)` for the first segment after the action. Route parameters only exist in Router files. Segments arrive as they stand in the URL, not decoded (the router decodes controller and action names, `getParameters` does not): `rawurldecode()` a segment that can carry more than a uuid, and `rawurlencode()` when building a path.
- Redirects: `$res->reroute([...parts])` for internal paths, `$res->rerouteUrl($url)` for a ready URL string. Never `header("Location: ...")`.
- Emails: a view renders the mail, the controller sends it with `$res->sendEmail(...)`, or `sendEmailToUser($userId, ...)` for registered users since it knows their address and language. A mail view must not leak data the recipient may not see. `sendEmail` returns `false` on failure and echoes the mailer error, which breaks a JSON response: check the return value and answer with an error the user sees. Signature, per language subjects and attachments: `docs/template-rendering-usages/sending-an-email.md`, `docs/guides/email.md`.
- Not used: `exit`, `die`, `http_response_code()`, `header()`, `setcookie()`, `echo`, `json_encode` of a response by hand. The framework has a method for each of these. If one really has no framework equivalent (a streamed export, for example), it is a last resort: leave a comment at the spot saying why, and report it as a finding.
- Never used, without the last resort: the superglobals `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, `$_SERVER`, `$_REQUEST`, and `$_SESSION` or `session_start()`. The request object is the only input source, and a stack keeps no session state (`SKILL.md`, "Stateless").
- **Fail visibly.** When an action or form branch fails, the user sees a specific message (`formErrors`, or the exception text the view shows) and the real exception goes to `logger()`. Never a generic "failed", never a swallowed exception. Debugging goes through `logger()` as well, never `echo`, `var_dump` or `print_r` in a view or an action; one such page once leaked account passwords.
- Never compute the application root in a controller. Views have `$opt["root"]`, emails have `$opt["absRoot"]`, and `$req->getRoot()` exists for the rare controller case.
- Type the signature as `(Request $req, Response $res)`. The classes are global aliases, no `use` statement needed.

## Splitting controllers

One controller per URL area the user perceives: `DashboardController`, `PositionController`, `ProfileController`, `SupportController`. If a controller grows beyond roughly 300 lines, split by audience (customer vs. support) or by entity, not by HTTP method.

## The dashboard index

Several roles share one start URL. `DashboardController::action_index` is the switch that sends each of them to their dashboard, checked from the most privileged role down, each branch returning:

```php
public function action_index(Request $req, Response $res) {
    $req->checkPermission("dashboard");

    // Supporters and sales have their own area
    if($req->checkPermission("support", true) || $req->checkPermission("sales", true)) {
        return $res->reroute(["support"]);
    }

    // A customer that finished the setup
    if($req->checkPermission("customer.isSetup", true)) {
        return $res->render("dashboard/customer_setup", [
            "company" => model("Company")->getByUserId(user()->userId),
        ]);
    }

    // A new customer sees the setup
    return $res->render("dashboard/customer");
}
```

The index mostly reroutes and does not load data of its own; the branches that need data have their own controller and URL (`SupportController`). At most the last, simplest view is rendered right here. Every role then has one bookmarkable start page under the same URL, without a role check inside any view.

## Layouts, aliases and CLI actions

- A whole controller that uses a non default layout sets it once with `Response::setGlobalDefaultLayout("support/layout")` in its `__construct`, instead of passing the third `render` argument in every action (`docs/core-features/layouts.md`).
- `action_index` answers the bare controller URL and `action_fallback` catches unknown action names. Use `action_fallback` only for slug style URLs; everything else is an action with parameters after it (`docs/core-features/controllers-and-actions.md`).
- Short or localized URLs are aliases (`docs/advanced-features/aliases-and-virtual-links.md`). Do not add a second controller or a route file to get a second URL for the same page.
- Cron jobs, imports and maintenance tasks are controller actions run through `php zubzet run`, not separate scripts (`docs/advanced-features/commands.md`). They live in the three fixed controllers of the next section.

## Cron, Worker and Routine controllers

Non interactive work has three fixed homes, named so anyone opening the project knows where to look. Do not invent a fourth (`JobController`, `TaskController`, `MaintenanceController`) or spread CLI actions over the feature controllers. All three are guarded by `checkPermission("console")`, which passes only in a CLI run, log with `logger()` and never `echo` a web page.

- **`CronController`**: every action a cron container runs on a schedule (`php zubzet run Cron <action>`). The permission check sits once in the constructor, so no action can forget it, and it is skipped in test mode so Cypress and a developer can trigger an action over HTTP. The constructor also prints one timestamped line per run: the container log is the cron log, and this is the one `echo` a stack has. Every action carries a one or two line comment saying what it does and for whom, because the method name alone does not tell an operator what the schedule triggers.

```php
<?php

    class CronController extends z_controller {

        public function __construct(Request $req, Response $res) {
            if(config("execution_type") != "test") {
                $req->checkPermission("console");
            }
            echo date("Y-m-d H:i:s") . " Running: " . $req->getParameters(-1, 1) . "\n";
        }

        // Password reminder for employers that never set one, three days after signup
        public function action_send_reminders(Request $req, Response $res) {
            model("Reminder")->sendDue();
        }

    }

?>
```

- **`WorkerController`**: the entry points of the stack's microservices and long running consumers (a queue worker, a RabbitMQ consumer, a listener a sidecar container starts). Same constructor pattern as the cron controller. The loop and the real work are in a model (`QueueModel`); the action only starts it. Each action is preceded by a short comment naming the microservice and what it processes (`// Micro service: click validation, consumes the click queue and marks fraud`), since this file is the map of the stack's services.
- **`RoutineController` and `RoutineModel`**: one-off changes that run exactly once and are then deleted: a data backfill after a migration, a bulk fix, an import of a legacy export. Both files are allowed to be messier than the rest of the stack and should touch as little of the project as possible, no shared helpers, no new model methods elsewhere: once the routine has run on every environment, the controller and the model are removed in a follow up commit, and nothing may depend on them. A routine is not a migration: migrations are schema, run everywhere automatically and are never deleted (`database.md`).

## Routers

Always prefer auto routing (`action_*` methods, Z.js for the stack's own pages): mixing the two styles is hard to read and understand for humans. `app/Routes/*.php` with `Route::get(...)` is for what auto routing cannot express, usually versioned APIs for external clients (`/api/v1/...`), webhooks and callback URLs; the folder also holds the middlewares those routes use. The stub `ExampleRouter.php` is a starting point and is replaced by real route files, or deleted when the project has none. Route and middleware details: `docs/core-features/routing.md`.

Routes in a route file follow REST: resources in a flat hierarchy (`/api/v1/segmentation/<id>`), the HTTP method carrying the verb. **`GET` never changes state and never reads a body** (proxies drop GET bodies silently). A `DELETE` route soft deletes like everything else, `active = 0` in the model, never a `DELETE` statement. **Every route sits behind a permission or key check like every action; a route without one is a bug.**

Putting a whole admin area behind `Route::group("/admin")` with a middleware that checks a shared secret key is wrong twice: everyone who knows the key is "logged in" without a user account, and everyone who is logged in has every permission. Admin pages are normal actions behind `checkPermission`, with the real users and roles of the project.
