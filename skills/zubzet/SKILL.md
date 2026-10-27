---
name: zubzet
description: >-
  Conventions for building and extending ZubZet stacks (PHP framework, composer
  package `zubzet/framework`): thin action_* controllers, SQL only in z_model
  classes, Blade views with Bootstrap 4 and one tag per line, Z.Forms before
  Z.Request.action, pages instead of modals, the fixed migration template, spaced
  seed ids, Cypress happy paths, S3 with signed links, and the framework's own
  helpers (model(), config(), user(), logger()) instead of self-built utilities.
  Use whenever a project depends on zubzet/framework, contains
  z_config/z_settings.ini, app/Controllers with z_controller classes, app/Models
  with z_model classes, app/Views/*.blade.php or a `zubzet` CLI file, or when the
  user mentions ZubZet, a "stack", Z.js, Z.Forms or z_admin, or asks to add a
  feature, view, model, migration or seed to such a project. Load it before
  reading or writing any code in those projects.
---

# Working on ZubZet projects

Small, readable, boring in the good sense. This file holds the rules that apply to every task. The files under `reference/` hold the full rules, templates and known agent failures per area. **Before writing code in an area, read its reference file completely.** The rules there are not repeated here.

## How strict these rules are

The rules are defaults that hold in almost every case; needing to skip one should not happen. Three kinds:

- **Security rules are never broken**: escaping, parameterized SQL, permission checks, numeric ids staying on the server, Galera safe migrations, files behind signed links. There is no good reason for an exception.
- **Everything else may be broken only for an obvious reason**, which is rare. When you think a rule makes no sense for the case at hand, ask instead of deciding on your own; nothing happens in the background without the user knowing.
- **When a feature cannot be built without circumventing a rule**, build it and state that as a finding: which rule, why, and what the framework would need so it does not happen again. The user reports it to the framework.

Start of every task in a ZubZet project:

1. Make sure this skill is linked into the project (see "Link the skill into the project").
2. Check `composer.json` for the `zubzet/framework` version and `app/Views` for `.blade.php` files. If a project still has `return ["body" => ...]` views, keep its format but follow every other rule here (see "Old patterns" at the end).
3. Read the project's `AGENTS.md` or `CLAUDE.md` for project specifics. They win over this skill where they differ.
4. Read the reference files for the areas the task touches (list below).
5. For any framework API question, research the docs (next section) instead of guessing.

## Research the docs

The docs are feature documentation: what exists and how it is called. This skill is the second layer: architecture, design and quality rules. Look up any API you are not sure about instead of guessing, and test that things work once you are done.

- Docs root: `vendor/zubzet/framework/docs/` (inside the framework repository itself: `docs/`). `mkdocs.yml` in the package root is the table of contents; read its `nav` first.
- Second source: `vendor/zubzet/framework/src/`, grep for the method name (`grep -rn "function sendEmail" vendor/zubzet/framework/src`). The admin panel and login views in `src/IncludedComponents/views` show the house style in Blade; the guides in `docs/guides/` (todo, shop, guest list, library) show the controller, model and view split end to end.
- When the docs and this skill disagree, the docs describe the API and the skill describes the design. Report the mismatch instead of guessing.

Question to page, relative to the docs root:

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
| Views, Blade directives, components | `core-features/views.md` |
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
| Maintenance mode, health endpoint | `core-features/maintenance.md`, `core-features/health-endpoint.md` |
| Admin panel, login as another user | `z-admin/usage.md`, `z-admin/login-as-another-user.md` |

## Link the skill into the project

This skill ships inside the `zubzet/framework` composer package, so every project gets the version that matches its installed framework. Claude Code only loads it automatically when the project links it. The first time you are pointed at this skill explicitly (by path, or by the installation docs) and the project does not link it yet, create the link from the project root:

```bash
mkdir -p .claude/skills
ln -s ../../vendor/zubzet/framework/skills/zubzet .claude/skills/zubzet
```

- Use exactly this relative target. It follows the framework version from `composer.lock`, so a `composer update` updates the skill too, and the link works on every machine and in CI.
- Only create it when `.claude/skills/zubzet` does not exist. If it exists as a real directory or points somewhere else, leave it alone and tell the user.
- Tell the user you created it and that it should be committed. Do not commit it yourself unless asked.
- Skip this step inside the framework repository itself. The version migrator creates the link on the 1.3.0 upgrade, so upgraded projects already have it.

## Reference files

Read the matching file before you write code in that area. A task that touches several areas needs several files.

- **Before writing or changing a controller, action or router**, read [reference/controllers.md](reference/controllers.md).
- **Before writing or changing a model or a class in `App\`**, read [reference/models.md](reference/models.md).
- **Before writing or changing a view, layout, component or inline JavaScript**, read [reference/views.md](reference/views.md).
- **Before building a form, a button that calls the server, or error handling**, read [reference/forms.md](reference/forms.md).
- **Before writing a migration or seed, or deleting data**, read [reference/database.md](reference/database.md).
- **Before handling uploads, downloads or S3**, read [reference/files-s3.md](reference/files-s3.md).
- **Before writing a Cypress spec or adding `data-test` attributes**, read [reference/testing.md](reference/testing.md).
- **When you need a request, response, base class, auth, Blade or Z.js capability**, find its docs page and source file in [reference/framework-api.md](reference/framework-api.md).
- **When reviewing a stack, and before calling your own work done**, read [reference/common-mistakes.md](reference/common-mistakes.md).

## The one paragraph version

A ZubZet stack is MVC with a thin controller. The controller checks permission, routes the request to the right branch (`isAction`, `hasFormData`, or render), hands data between model and view, and nothing else. Models own every SQL statement and every bigger piece of work (imports, external APIs, S3). Views are Blade templates made of Bootstrap 4 utility classes, one tag per line, formatting only, with their JavaScript inline at the bottom. Every create, edit or detail flow is a page with its own URL, not a modal. The UI talks to the backend through Z.Forms first, `Z.Request.action` for buttons, and nothing else unless it truly cannot be done otherwise. Every table has the same five base columns, nothing is ever hard deleted, every feature ships seed rows and a Cypress happy path, and user files live in S3 behind signed links.

The framework already provides login, permissions, forms, migrations, helpers like `model()`, `config()`, `user()` and `logger()`, and dozens of smaller functions; a stack uses them and never rebuilds them.

If something you are about to write does not fit one of those sentences, stop and check this skill.

Many conventions here are one choice among equals (singular table names, `$sql` formatting, data-test names). They are fixed so every project reads the same: consistency beats personal preference, so follow the convention instead of reopening it.

## The hierarchy: where does code go?

This is the rule agents violate most. Memorize the levels and the test for each one.

| Level | Allowed | Examples | Not allowed |
| --- | --- | --- | --- |
| 1. View | Formatting one-liners | `date("d.m.Y", strtotime($x))`, `floor((time() - strtotime($x)) / TIMESPAN_DAY_1)`, `count($items)`, `$plan->name()`, ternaries for a class name | Loops that compute, array_filter/array_map, lookups, building arrays, SQL, anything you would call "work" |
| 2. Controller | Permission checks, reading request input, validation (`FormField`), picking the branch, light reshaping of data for the view (group a list in two, build a filter list), calling models, choosing the response | An action that splits positions into open and accepted and builds filter arrays | Business rules, SQL, parsing files, HTTP calls to other systems, user facing texts, computing paths, 600 line controllers |
| 3. Model, database | Every SQL statement, exclusively | `PositionModel::getByUser()`, `setState()`, `getRequests()` | SQL anywhere else, including controllers, views, helpers, migrations run from code |
| 4. Model, non database | Bigger logic, imports, external APIs, S3, queues, anything reused | `ImportModel::contactsFromFile()`, `S3Model`, `QueueModel`, `IntegrationModel::request()` | Putting this into the controller because "it is only used once" |
| 5. `App\` namespace | Complex flows, enums with behavior, value objects, services with state | `App\Objects\Plan` (enum with `icon()`, `description()`), `App\Objects\S3Presign` | Dumping helper functions there, or making an `App\Service` for three lines that belong in a model |

Two heuristics:

- **If the controller method scrolls, split.** Either into a model method (preferred) or into a second controller when the URLs naturally separate (`PositionController` for the customer, a `SupportController` for the back office).
- **A view never decides, a controller never knows SQL, a model never renders.** Check each file against that sentence before finishing.

Keep the hierarchy, but do not overdo it. One rounding operation does not need a model method. An enum for two string values nobody branches on is complexity, not structure.

## Use the framework before writing anything yourself

ZubZet ships helpers for almost every recurring need. Before writing a utility, a lookup, a wrapper or a "small helper function", check this table, [reference/framework-api.md](reference/framework-api.md) and the docs. If the framework has it, use it. Agents building their own versions of these is one of the main sources of low quality stacks.

Global helper functions, documented in `vendor/zubzet/framework/docs/core-features/global-helper-functions.md` (source: `src/Support/GlobalReferences.php`, `src/Support/Helpers.php`):

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
| `isCli()` | Web versus `php zubzet` branch | Scattered `php_sapi_name()` checks |
| `e($value)` | Escaping outside `{{ }}` | `htmlspecialchars` with hand picked flags |
| `makeSlug($str)` | URL and `data-test` slugs | Custom regex replacements |
| `shortenStr($str, $max)` | Truncating with an ellipsis | `substr` plus `...` |
| `emptyToNull($value)` | `""` / `"null"` to `null` before binding | Ternaries on every field |
| `de_strtolower($str)` | Lowercasing German text | `mb_strtolower` with manual replacements |

Constants (`src/Core/Constants.php`): `FILE_SIZE_1MB` ... `FILE_SIZE_100GB` for upload limits, `TIMESPAN_DAY_1` ... `TIMESPAN_DAY_365` for date arithmetic, `UPLOAD_SUCCESS` and `UPLOAD_ERROR_*` for upload results. Never write `86400` or `10485760` yourself.

Framework features that replace whole subsystems (each has a docs page, see "Research the docs"): login, logout, password reset, signup and email verification; password hashing; the `/z` admin panel (users, roles, groups, organizations, logs, database, maintenance); login-as; permission wildcards and organizations for grouping users; migrations and seeds; console commands that run any action from the CLI; URL aliases; per section default layouts; email with per language subjects and attachments; logging channels; custom error and maintenance pages by overriding the framework view; health endpoint; maintenance mode; error page with stack trace and debug bar in test mode; the asset proxy that serves the bundled jQuery, Bootstrap, Font Awesome and Z.js. None of these are rebuilt in a stack. If one of them seems to be missing a feature, say so instead of working around it.

## Project layout

```
.
├── app/
│   ├── Controllers/        PascalCaseController.php, one per URL area
│   ├── Models/             PascalCaseModel.php, SQL and bigger logic
│   ├── Objects/            App\Objects: enums, value objects (namespaced, PSR-4 via composer.json)
│   ├── Routes/             only for /api/... routes, otherwise empty (ExampleRouter.php stays)
│   ├── Views/
│   │   ├── layout/         default_layout.blade.php, min_layout, empty, mail_layout
│   │   ├── components/     <x-...> components shared by several views
│   │   ├── <area>/         customer/, support/, dashboard/, notification/ ...
│   │   └── login*.blade.php, email_*.blade.php
│   └── Database/
│       ├── migrations/     YYYY-MM-DD_name.sql
│       └── seed/           <area>/<table>.sql plus accounts/0_permissions.sql, accounts/1_users.sql
├── webroot/
│   ├── assets/css/main.css        the only project stylesheet
│   ├── assets/css/webfonts/       self hosted fonts (woff2)
│   ├── assets/img/                brand, favicon, illustrations (never user uploads)
│   └── assets/js/                 vendored libraries the framework does not ship (chart, dataTables)
├── tests/cypress/                 e2e/<area>/<feature>.cy.js, fixtures/logins.json, support/commands.js
├── z_config/z_settings.ini        all configuration, overridable through CONFIG_<KEY> env vars
├── packaging/                     Dockerfiles, docker-compose, Caddyfile
├── composer.json                  zubzet/framework plus very few extras
├── package.json                   npm scripts: start, stop, seed, tests, cypress
├── README.md                      what it is, how to start
└── AGENTS.md                      project specifics for agents (short)
```

- No `app/helpers.php`, no `tools/`, no `app/Translations/` hand rolled i18n, no custom bootstrap files. If the framework does not have it, it goes into a model or `App\`.
- Controllers, models and views are discovered by name. Do not register them anywhere.
- `webroot/assets` is for static, versioned, project owned files. User uploaded files never land there. jQuery, Bootstrap, Font Awesome and Z.js are not copied there either: `<x-zubzet::head>` loads them through the asset proxy (`docs/core-features/asset-proxy.md`).
- Keep `.vscode/`, `.drone.yml` or `.forgejo/workflows/`, `packaging/` as they come from the template. Do not restructure them.

## Naming and language

- **All identifiers are English.** Controllers, actions, models, methods, variables, columns, JS, CSS classes, data-test values, commit messages, comments. Older stacks may have German controller names because they form the URL; new stacks use English names anyway (`PositionController::action_new`). If a project needs German URLs, that is an alias (`docs/advanced-features/aliases-and-virtual-links.md`), not a reason to name a class in German.
- **UI texts are in the project's UI language** (usually German). Texts live in views only. A controller or model does not contain sentences meant for a human, with one tolerated exception: an exception message that a view will show verbatim (see the errors section in [reference/forms.md](reference/forms.md)).
- **Comments** are English, sparse, and explain why, not what. A comment that restates the code is deleted. Doc blocks on every method are noise.
- Casing: `PascalCaseController`, `action_snake_case`, `camelCaseMethod()`, `$camelCaseVariable`, singular `snake_case_table` (`company`, not `companies`), `snake_case_column`, camelCase for foreign key columns (`userId`, `positionId`), `kebab-case` for data-test values, action names and CSS classes, `UPPER_SNAKE` for constants and enum cases.
- Permission names: `area.action`, lowercase, dotted: `customer.dashboard`, `customer.account`, `support`, `dashboard.admin`. Wildcard `*.*` only for the superadmin role in seeds.
- Prefer expressive variable names, because they are easier to read: `$position`, not `$p`; `$observation`, not `$o`; `$rootDirectory`, not `$rd`. Loop variables are the singular of the collection. Abbreviations everyone uses anyway stay (`$req`, `$res`, `$sql`, `$i`, `$e`), and so does a table alias inside a single SQL query (`cu` for `company_user`): it lives for a few lines and keeps the query's structure readable.

## Authentication and permissions

- Login, logout, password reset, verification, "login as" (`/z/login-as/<userId>`) and the admin panel (`/z`) come from the framework. Do not write a login, a key check, a token middleware or a session handler.
- Passwords are never hashed, compared or stored by project code: `User::add()`, `$user->updatePassword()`, `$user->verifyPassword()` (`docs/core-features/password-handling.md`). Users, roles, organizations and sessions go through the objects in `docs/core-features/access-control.md`, never through SQL on `z_user`, `z_role`, `z_organization` or `z_logintoken` outside seeds.
- Roles and permissions are seeded in `app/Database/seed/accounts/0_permissions.sql`: `z_role` rows, `z_role_permission` rows. The superadmin role gets `*.*`. Each customer facing role gets a short dotted list.
- Users for development and tests are seeded in `accounts/1_users.sql` with a known password hash, plus login tokens with fixed values (`aaaa...01`) that Cypress uses to log in without the login form (table `z_logintoken`, cookie `z_login_token`).
- Check permissions carefully, rather more than less, each at its level: the actual access check in the controller (`$req->checkPermission`), display decisions in the view (`@auth("x")` or `$opt["user"]->checkPermission("x")`), row level access in the model (`userId = user()->userId`). Some paths need all three, a public page needs none; what matters is that every access path is covered. `checkPermission` sends anonymous users to the login page and logged in users without the permission to 403; `checkSuperPermission` additionally accepts the permission of the real user behind a login-as session, so use it only for support tooling that must stay reachable while a supporter is logged in as a customer.
- Current user: `user()` helper in models and controllers, `$opt["user"]` in views. Fields: `userId`, `isLoggedIn`, `checkPermission()`.

## Configuration

- Everything configurable lives in `z_config/z_settings.ini` as `key = value`. No comment lines (the parser treats `;` and `#` as part of the value). Keys are snake_case, grouped by prefix (`mail_*`, `s3_*`, `logger_*`).
- Every key can be overridden by `CONFIG_<KEY>` environment variables because `allow_env_config = true`. Secrets in production come from the environment, the ini holds development values.
- Read with `config("key")` everywhere, including model constructors. Never `getenv()`, `$_ENV` or `$booter->req->getBooterSettings()` in new code.
- `z_automated_setting.ini` is written by the framework and git ignored.
- Bump `assetVersion` whenever CSS or JS in `webroot/assets` changes, so `generateResourceLink` busts caches.
- `execution_type = test` marks development and CI: it switches on the Whoops error page with stack traces and the debug bar, so production runs `prod`. Code may skip external side effects (S3 delete, outgoing mail) only on that flag, never on a hostname check.
- `defaultIndex = DashboardController` picks the start page. No `Route::get("/")`.

## Dependencies and assets

- No CDN, for data privacy: a CDN request hands every visitor's IP address to a third party, which B2B projects cannot do without an explicit decision. Libraries, fonts, icon sets and CSS live in the repository: download the pinned build into `webroot/assets/js` or `webroot/assets/css` and reference it through `generateResourceLink`.
- Fonts are self hosted woff2 with `@font-face` in the layout or `main.css`.
- Composer: `zubzet/framework` plus very few packages for real complexity (`async-aws/s3`, `parsecsv/php-parsecsv`, `symfony/uid`, `php-amqplib/php-amqplib`). Never a package for something that is twenty lines of PHP.
- npm is for tooling only (Cypress, docker-compose scripts). No frontend build step, no SCSS, no TypeScript, no bundler: this is an explicit decision. With a really good reason you may ask; that is the most on offer.
- Bootstrap 4, jQuery, Font Awesome 4 class names (via the v4 shims), Chart.js and DataTables are the frontend toolkit. The first three ship with the framework (`components/bootstrap` 4.6, `components/jquery`, `components/font-awesome` 6 with v4 shims) through the asset proxy. Nothing else without a reason written down in AGENTS.md.

## Every feature ships

What it needs: a migration when the schema changes, seed rows covering its states when it has data, and a Cypress spec when it can be tested, which is nearly always. Nothing is forced where there is nothing to ship, but if it can be tested, test it. `npm run tests` must stay green.

## Git, commits, repository docs

These are the defaults for stacks. A project's AGENTS.md may define its own commit style.

- Commit subject: gitmoji, space, English imperative, under about 70 characters. Body only when the subject cannot carry it. Ticket references at the end: `Closes #2786`, `refs #2759`.
  - `✨ Add contact workspace filter` for features, `🐛 Fix delete button on requests page`, `💄 Make requests page work on mobile`, `⬆️ Update ZubZet to 1.3`, `🔧` for config, `📝` for docs, `✅` for tests, `👷` for CI, `🔒` for security, `🎉` for the initial commit.
- Author is the configured git identity. No `Co-authored-by` or session trailers.
- `README.md`: name, one paragraph purpose, how to start (`npm run start`), how to test. Short.
- `AGENTS.md`: project specifics an agent cannot infer: customer, deployment target, odd constraints, where the design comes from. Short. It references this skill instead of repeating it.
- Branch `main`, deploy by push (Drone or Forgejo Actions). Do not touch pipeline files unless asked.

## Before you call a feature done

- [ ] Every access path checks permissions; ownership is in the model query.
- [ ] Controller reads top to bottom: permission, load, actions, form, render. Under ~80 lines per action.
- [ ] Every SQL statement is in a model, parameterized, formatted as in `models.md`, filters `active = 1` in WHERE and every JOIN, soft deletes; lists have an explicit `ORDER BY`.
- [ ] Input is collected by Z.Forms; buttons use `Z.Request.action`; nothing custom unless mirrored into a hidden Z.Forms field.
- [ ] View: `@extends($layout)`, one tag per line, four spaces, Bootstrap only, no `style=`, no `<p>` (it carries its own margin and typographic defaults, so spacing drifts from the utility classes), headings in order, `{{ }}` for output (raw only for values that cannot carry input, `views.md`), `json_encode` inside `<script>`, German dates via `strtotime`, inline `<script>` at the end.
- [ ] Every interactive element and status indicator has a `data-test`.
- [ ] New or edit flows are pages with their own URL; modals only for confirmations and tiny secondary forms.
- [ ] A schema change follows the migration template (id, uuid, FKs, columns, active, updated, created, indexes). Seed rows cover each state the feature has.
- [ ] A Cypress spec covers the happy path of everything that can be tested; `npm run tests` is green.
- [ ] Every rule you had to bend is reported as a finding, and every rule you wanted to bend was asked about first.
- [ ] Files go to S3 with private ACL and signed links; nothing user generated under `webroot/`.
- [ ] No CDN, no new helper file, no new global function, no new Composer package without a reason.
- [ ] Settings via `config()`, models via `model()`, current user via `user()`, logging via `logger()`, limits via `FILE_SIZE_*` / `TIMESPAN_*`; nothing rebuilt that the framework already has.
- [ ] `assetVersion` bumped if `webroot/assets` changed.
- [ ] Commit follows the project's style.

## Old patterns

Projects on older framework versions or older templates still contain these. Keep them consistent inside such a project, do not introduce them in new code.

- Views as PHP files returning `["body" => ...]` arrays instead of Blade. Keep the format when the project uses it, apply every markup rule from [reference/views.md](reference/views.md) anyway.
- `z_models/` and `z_controllers/` directories instead of `app/Models` and `app/Controllers`.
- `$req->getModel()`, `$req->getRequestingUser()` and `$booter->req->getBooterSettings()` instead of `model()`, `user()` and `config()`.
- Copies of jQuery, Bootstrap and Z.js in `webroot/assets/js` from before the asset proxy (framework 1.2.0).
