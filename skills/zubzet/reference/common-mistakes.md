# Common agent mistakes in ZubZet stacks

Collected from an agent built prototype that violated most rules of this skill. Use the list to review a stack, or your own work before calling it done.

## Structure

- An `app/helpers.php` with a set of global prefixed functions: locale detection, a translation catalogue loader, URL building, year formatting. A hand rolled i18n system next to the framework. Violates the project layout (no helpers), the `App\` rules, and the hierarchy (formatting helpers belong to a view or an enum).
- An admin area protected by a secret key in `?key=` and a cookie, with `hash_equals` and manual `setcookie`. The framework's login and permission system was ignored.
- All URLs in a `Route::group` with middlewares, including plain pages. Auto routing comes first; routers are for what it cannot express, usually external APIs.
- Static model methods that shape API output (`publicView()`) or do geometry inside a database model.
- A controller that computes business values and builds export rows, then writes CSV with `fputcsv` to `php://output` and calls `exit`.
- The application root reimplemented in two controllers instead of using `$opt["root"]` or `$req->getRoot()`.
- `http_response_code(404)` and `exit` sprinkled in actions.
- Business texts and validation messages as German sentences in controllers.
- `password_hash()` / `password_verify()` in a project model, or a hand made impersonation flow, instead of `User::add()`, `updatePassword()`, `verifyPassword()` and `$res->loginAs()`.
- A `bin/cron.php` or `scripts/import.php` with its own bootstrap instead of an action run through `php zubzet run <Controller> <action>`.
- The same page reachable under a second URL through a second controller instead of an alias reroute.

## Views

- Several tags per line, whole `<tr>` rows on one line, `<?php foreach ... : ?>` with `endforeach`, `<?php if ... : ?><dt>..</dt><?php endif; ?>` inline.
- Variables named `$b`, `$p`, `$o`, `$w`, `$v`, `$k`.
- A custom CSS framework with its own button, badge and table classes instead of Bootstrap; `style="margin-bottom:10px"`, `style="white-space:nowrap"`, absolute positioning with pixel values in `style`.
- `<p>` tags, `<dl>` metadata lists, plain `<table>` for an admin list: a table is not mobile friendly compared to a list built with cards and the grid. DataTables is the exception for real data grids.
- Libraries from `unpkg.com` and `cdn.jsdelivr.net`.
- Five project JS files in `webroot/assets/js` for a four page prototype instead of inline scripts.
- User images served from `webroot/assets/img/...` with the path assembled in a model, instead of S3 with signed links.
- `window.*` globals for every page and a JS translation layer.
- Markup fragments concatenated in PHP: `'<span class="badge">v' . (int) $row["version"] . '</span>'`.
- No `data-test` attributes at all, no Cypress specs beyond the template login test.
- A `$opt["page"]` flag per render call to drive navbar state and body classes.
- Laravel directives Katana does not have (`@csrf`, `@can`, `@error`, `@lang`) left in templates.
- A hardcoded layout name in `@extends("layout.default_layout")` instead of `@extends($layout)`.
- Copies of jQuery, Bootstrap or Z.js in `webroot/assets/js` although `<x-zubzet::head>` serves them through the asset proxy.

## Data

- Table prefixes, `ENUM` columns, `DATETIME`, named indexes like `idx_pose_image`, no `uuid`, no `active`, `image_id` instead of `imageId`.
- Demo rows inside the migration file instead of `seed/`.
- Status strings validated with `in_array($status, ["draft", "published", "hidden"], true)` in three places instead of one enum with behavior, or one model method.

## Process

- German, multi-sentence commit subjects without gitmoji.
- `AGENTS.md` documenting workarounds ("no `Route::get("/")`", "no semicolons in data") instead of removing the cause.
