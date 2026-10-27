# Controllers

Framework docs: `vendor/zubzet/framework/docs/core-features/controllers-and-actions.md`, `routing.md`, `rest-api.md`.

## Contents

- Shape of an action
- Rules
- Splitting controllers
- Layouts, aliases and CLI actions
- Routers

## Shape of an action

Every action follows the same order. It is well tested and proven, so deviate from it only with a very good reason.

```php
<?php

    class PositionController extends z_controller {

        public function action_details(Request $req, Response $res) {
            // 1. Permission, always the first statement
            $req->checkPermission("customer.dashboard");

            // 2. Load what every branch needs
            $position = model("Position")->get($req->getParameters(0, 1));
            if(empty($position)) {
                return $res->reroute(["position"]);
            }

            // 3. Async branches, each one returns
            if($req->isAction("update-contact-workspace")) {
                model("Position")->updateContactWorkspace(
                    $position["id"],
                    $req->getPost("contactUuid"),
                    filter_var($req->getPost("workspace_contacted_at"), FILTER_VALIDATE_BOOLEAN),
                    $req->getPost("workspace_notes"),
                );
                return $res->success();
            }

            // 4. Form branch (Z.Forms submit)
            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new \FormField("name"))->required()->length(1, 255),
                ]);
                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
                $res->updateDatabase("position", "id", "i", $position["id"], $formResult);
                return $res->success();
            }

            // 5. Render, last statement
            return $res->render("customer/position_details", [
                "position" => $position,
                "contacts" => model("Position")->getContacts($position["id"]),
            ]);
        }

    }

?>
```

## Rules

- Permissions are checked carefully, rather more than less: `$req->checkPermission("...")` as the first statement of every action that needs a login, including actions that only answer AJAX. The boolean form `checkPermission("support", true)` is for deciding what to do instead of showing the login or 403 page: branch between two views for two roles, pass a `$canViewAny` flag to a model, hide a part of the page. When the boolean form is used, the ordering is naturally different; what matters is that every access path is covered. A public page has no check, and says so in a comment.
- Record ownership is not checked in the controller. The model query carries `WHERE userId = ?`. The controller only handles "not found" by rerouting.
- One `isAction` block per button type, each ending in `return $res->success([...])`. Order the blocks: reads first, then writes, then the form, then render.
- A controller passes data to the view as a flat associative array with descriptive keys. It may group or split a list for the view, build filter option arrays, and merge two model results. It does not loop over rows to compute business values.
- Use the global helpers: `model("Name")`, `user()->userId`, `config("key")`, `logger()`. `$req->getModel()` and `$req->getRequestingUser()` still work but new code uses the helpers everywhere.
- URL parameters: `$req->getParameters(0, 1)` for the first segment after the action. Route parameters only exist in Router files.
- Redirects: `$res->reroute([...parts])` for internal paths, `$res->rerouteUrl($url)` for a ready URL string. Never `header("Location: ...")`.
- A destructive `isAction` branch checks its own finer permission after the action level check (`checkPermission("shop.view")` at the top, `checkPermission("shop.delete")` inside `isAction("delete-product")`), as the guides do (`docs/guides/shop.md`, `docs/guides/todo.md`).
- `checkPermission` sends anonymous users to the login page and logged in users without the permission to the 403 page (`docs/core-features/permission-system.md`); never render a custom "forbidden" view from the boolean form.
- Emails: a view renders the mail, the controller sends it with `$res->sendEmail(...)`, or `sendEmailToUser($userId, ...)` for registered users since it knows their address and language. A mail view must not leak data the recipient may not see. Signature, per language subjects and attachments: `docs/template-rendering-usages/sending-an-email.md`, `docs/guides/email.md`.
- Not used: `exit`, `die`, `http_response_code()`, `header()`, `$_GET`, `$_POST`, `$_FILES`, `$_COOKIE`, `$_SERVER`, `setcookie()`, `echo`, `json_encode` of a response by hand. The framework has a method for each of these. If one really has no framework equivalent (a streamed export, for example), it is a last resort: leave a comment at the spot saying why, and report it as a finding.
- Never compute the application root in a controller. Views have `$opt["root"]`, emails have `$opt["absRoot"]`, and `$req->getRoot()` exists for the rare controller case.
- Type the signature as `(Request $req, Response $res)`. The classes are global aliases, no `use` statement needed.

## Splitting controllers

One controller per URL area the user perceives: `DashboardController`, `PositionController`, `ProfileController`, `SupportController`. If a controller grows beyond roughly 300 lines, split by audience (customer vs. support) or by entity, not by HTTP method.

## Layouts, aliases and CLI actions

- A whole controller that uses a non default layout sets it once with `Response::setGlobalDefaultLayout("support/layout")` in its `__construct`, instead of passing the third `render` argument in every action (`docs/core-features/layouts.md`).
- `action_index` answers the bare controller URL and `action_fallback` catches unknown action names. Use `action_fallback` only for slug style URLs; everything else is an action with parameters after it (`docs/core-features/controllers-and-actions.md`).
- Short or localized URLs are aliases (`docs/advanced-features/aliases-and-virtual-links.md`). Do not add a second controller or a route file to get a second URL for the same page.
- Cron jobs, imports and maintenance tasks are controller actions run through `php zubzet run`, not separate scripts (`docs/advanced-features/commands.md`). Such an action starts with `$req->checkPermission("console")`, which passes only in a CLI run, logs with `logger()`, and never `echo`s a web page.

## Routers

Always prefer auto routing (`action_*` methods, Z.js for the stack's own pages): mixing the two styles is hard to read and understand for humans. `app/Routes/*.php` with `Route::get(...)` is for what auto routing cannot express, usually versioned APIs for external clients (`/api/v1/...`), webhooks and callback URLs. Route and middleware details: `docs/core-features/routing.md`.

Putting a whole admin area behind `Route::group("/admin")` with a middleware that checks a secret key is wrong twice: it bypasses the permission system and the login system.
