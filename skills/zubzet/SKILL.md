---
name: zubzet
description: >-
  Conventions for building and extending ZubZet stacks (PHP framework, composer
  package `zubzet/framework`): controllers, models, views, Z.Forms,
  migrations, seeds, Cypress specs and S3. Use whenever a project depends on
  zubzet/framework, contains z_config/z_settings.ini, app/Controllers with
  z_controller classes, app/Models with z_model classes or app/Views,
  or when the user mentions ZubZet, a "stack", Z.js,
  Z.Forms or z_admin. Load it before reading or writing any code in those
  projects.
---

# Working on ZubZet projects

Small, readable, boring in the good sense. This file holds the rules that apply to every task. The files under `code-style/` and `reference/` hold the full rules, templates and known agent failures per area; they are not repeated here.

## How strict these rules are

The rules are defaults that hold in almost every case; needing to skip one should not happen. Three kinds:

- **Security rules are never broken**: escaping, parameterized SQL, permission checks, numeric ids staying on the server, Galera safe migrations, files behind signed links, no PHP superglobal and no session (see "Stateless"), and no secret or API key as a literal anywhere in the code: every secret is read with `config()` from `z_settings.ini` or a `CONFIG_<KEY>` environment variable. There is no good reason for an exception.
- **Everything else may be broken only for an obvious reason**, which is rare. When you think a rule makes no sense for the case at hand, ask instead of deciding on your own; nothing happens in the background without the user knowing.
- **When a feature cannot be built without circumventing a rule**, build it and state that as a finding: which rule, why, and what the framework would need so it does not happen again. The user reports it to the framework.

## What to read

At the start of every task in a ZubZet project:

1. `.claude/skills/zubzet` must exist in the project. If not, follow [reference/link-skill.md](reference/link-skill.md).
2. The project's `AGENTS.md` or `CLAUDE.md` wins over this skill where they differ. Check `composer.json` for the `zubzet/framework` version: this copy of the skill ships with the 1.2 line, whose views are PHP files returning `head` and `body` closures.
3. For any framework API question, look it up (next section) instead of guessing.

**Whenever the task writes code, even one line**, read [code-style/index.md](code-style/index.md) (whitespace, casing, names) and the file of each language touched: [php.md](code-style/php.md), [sql.md](code-style/sql.md), [html.md](code-style/html.md), [css.md](code-style/css.md), [javascript.md](code-style/javascript.md). They are short, and the style is not what a formatter or reflex produces.

The area files hold the design rules; read them before writing in their area, as deep as the task warrants:

| Before you ... | Read |
| --- | --- |
| write or change a controller, action or router | [reference/controllers.md](reference/controllers.md) |
| write or change a model or a class in `App\` | [reference/models.md](reference/models.md) |
| write or change a view, layout or inline JavaScript | [reference/views.md](reference/views.md) |
| build a form, a button that calls the server, or error handling | [reference/forms.md](reference/forms.md) |
| write a migration or seed, or delete data | [reference/database.md](reference/database.md) |
| handle uploads, downloads or S3 | [reference/files-s3.md](reference/files-s3.md) |
| write a Cypress spec or add `data-test` attributes | [reference/testing.md](reference/testing.md) |
| need a request, response, base class, auth or Z.js capability, or a docs page | [reference/framework-api.md](reference/framework-api.md) |
| review a stack, or call your own work done | [reference/common-mistakes.md](reference/common-mistakes.md) |

- **A small fix** (a typo, a wrong condition, one query): no area files. Match the surrounding code, it was written under these rules. Open one section of one file only when the fix touches something the surrounding code does not show.
- **A medium change** (a new action, a new model method, a new section of a page): the sections of the area files that cover what you write; skim the rest for relevant headings.
- **A large feature or a long session**: the area files for every area you touch, completely, before writing. Then, because context fades over a long session, **run a conformance pass**: at a natural break in the middle and always at the end, re-read "Before you call a feature done" and the style files for the languages you wrote, and go through your diff against them. Fixing what you find is part of the feature, not a follow-up.

## Research the docs

The docs are feature documentation: what exists and how it is called. This skill is the second layer: architecture, design and quality rules. Look up any API you are not sure about, and test that things work once you are done.

- Docs root: `vendor/zubzet/framework/docs/` (inside the framework repository itself: `docs/`). `mkdocs.yml` in the package root is the table of contents; read its `nav` first. The question-to-page table is in [reference/framework-api.md](reference/framework-api.md).
- Second source: `vendor/zubzet/framework/src/`, grep for the method name (`grep -rn "function sendEmail" vendor/zubzet/framework/src`). The admin panel and login views in `src/IncludedComponents/views` show the house style; the guides in `docs/guides/` show the controller, model and view split end to end.
- When the docs and this skill disagree, the docs describe the API and the skill describes the design. Report the mismatch instead of guessing.

## The one paragraph version

A ZubZet stack is MVC with a thin controller. The controller checks permission, routes the request to the right branch (`isAction`, `hasFormData`, or render), hands data between model and view, and nothing else. Models own every SQL statement and every bigger piece of work (imports, external APIs, S3). Views are PHP files returning `head` and `body` closures, made of Bootstrap 4 utility classes, one tag per line, formatting only, with their JavaScript inline at the bottom. Every create, edit or detail flow is a page with its own URL, not a modal. The UI talks to the backend through Z.Forms first, `Z.Request.action` for buttons, and nothing else unless it truly cannot be done otherwise. Every table has the same five base columns, nothing is ever hard deleted, every feature ships seed rows and a Cypress happy path, and user files live in S3 behind signed links.

The framework already provides login, permissions, forms, migrations, helpers like `model()`, `config()`, `user()` and `logger()`, and dozens of smaller functions; a stack uses them and never rebuilds them.

If something you are about to write does not fit one of those sentences, stop and check this skill.

## The hierarchy: where does code go?

This is the rule agents violate most. Memorize the levels and the test for each one.

| Level | Allowed | Examples | Not allowed |
| --- | --- | --- | --- |
| 1. View | Formatting one-liners | `date("d.m.Y", strtotime($x))`, `count($items)`, `$plan->name()`, ternaries for a class name | Loops that compute, array_filter/array_map, lookups, building arrays, SQL, anything you would call "work" |
| 2. Controller | Permission checks, reading request input, validation (`FormField`), picking the branch, light reshaping of data for the view (group a list in two, build a filter list), calling models, choosing the response | An action that splits positions into open and accepted and builds filter arrays | Business rules, SQL, parsing files, HTTP calls to other systems, user facing texts, computing paths, 600 line controllers |
| 3. Model, database | Every SQL statement, exclusively | `PositionModel::getByUser()`, `setState()`, `getRequests()` | SQL anywhere else, including controllers, views, helpers, migrations run from code |
| 4. Model, non database | Bigger logic, imports, external APIs, S3, queues, anything reused | `ImportModel::contactsFromFile()`, `S3Model`, `QueueModel`, `IntegrationModel::request()` | Putting this into the controller because "it is only used once" |
| 5. `App\` namespace | Complex flows, enums with behavior, value objects, services with state | `App\Objects\Plan` (enum with `icon()`, `description()`), `App\Objects\S3Presign` | Dumping helper functions there, or making an `App\Service` for three lines that belong in a model |

Two heuristics:

- **If the controller method scrolls, split.** Either into a model method (preferred) or into a second controller when the URLs naturally separate (`PositionController` for the customer, a `SupportController` for the back office).
- **A view never decides, a controller never knows SQL, a model never renders.** Check each file against that sentence before finishing.

Keep the hierarchy, but do not overdo it. One rounding operation does not need a model method. An enum for two string values nobody branches on is complexity, not structure.

## Use the framework before writing anything yourself

Before writing a utility, a lookup, a wrapper or a "small helper function", check [reference/framework-api.md](reference/framework-api.md). Agents building their own versions of what is already there is one of the main sources of low quality stacks.

The global helpers are `model()`, `config()`, `user()`, `request()`, `response()`, `logger()`, `view()`, `db()`, `isCli()`, `e()`, `makeSlug()`, `shortenStr()`, `emptyToNull()` and `de_strtolower()`, plus the `FILE_SIZE_*`, `TIMESPAN_*` and `UPLOAD_*` constants. Never write `86400` or `10485760` or a wrapper around `$_POST` yourself. The whole subsystems the framework ships (login, admin panel, migrations, console, email, logging, error pages, asset proxy and more) are tabled in the same file; none of them is rebuilt in a stack, and if one seems to be missing a feature, say so instead of working around it.

## Keep it simple

Asked for more often than anything else:

- **The smallest change that solves the task.** Touch only what the task needs; no restructuring, reformatting or improving of code nearby. A one-token fix beats three new helpers.
- **No speculative code.** No fallback for a state the backend already guarantees, no check nobody asked for, no setting for a need nobody has yet. Add it when the need is real.
- **No helper for a single caller.** Inline it. Extract only a real unit with a clear responsibility.
- **Simplify after it works.** Before the task is done, drop dead code, unused parameters, leftover test files and your own explanatory comments. Readable beats short; be able to explain every line.

## Project layout

```
.
├── app/
│   ├── Controllers/        PascalCaseController.php, one per URL area; CronController, WorkerController,
│   │                       RoutineController for scheduled, service and one-off work (controllers.md)
│   ├── Models/             PascalCaseModel.php, SQL and bigger logic; subfolders per area once the list grows
│   ├── Objects/            App\Objects: enums, value objects (namespaced, PSR-4 via composer.json)
│   ├── Routes/             route files and middlewares for what auto routing cannot express (the stub ExampleRouter.php is replaced, not kept)
│   ├── Views/
│   │   ├── layout/         default_layout.php, min_layout, empty, mail_layout
│   │   ├── <area>/         customer/, support/, dashboard/, notification/ ...
│   │   └── login*.php, email_*.php
│   └── Database/
│       ├── migrations/     YYYY-MM-DD_name.sql
│       └── seed/           one file per area or feature, plus accounts/0_permissions.sql, accounts/1_users.sql
├── webroot/
│   ├── assets/css/main.css        the project stylesheet (a second one only for a large self-contained area)
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

- No `app/helpers.php`, no `tools/`, no hand rolled i18n layer (UI texts live in the views, see "Language"), no custom bootstrap files. If the framework does not have it, it goes into a model or `App\`.
- Controllers, models and views are discovered by name. Do not register them anywhere.
- `webroot/assets` is for static, versioned, project owned files. User uploaded files never land there.
- Keep `.vscode/`, `.drone.yml` or `.forgejo/workflows/`, `packaging/` as they come from the template. Do not restructure them.

## Language

Casing, variable names and every other formatting rule are under [code-style/](code-style/index.md). This section is only about which language a text is in.

- **All identifiers are English.** Controllers, actions, models, methods, variables, columns, JS, CSS classes, data-test values, commit messages, comments. Older stacks may have German controller names because they form the URL; new stacks use English names anyway (`PositionController::action_new`). If a project needs German URLs, that is an alias (`docs/advanced-features/aliases-and-virtual-links.md`), not a reason to name a class in German.
- **UI texts are in the project's UI language** (usually German). Texts live in views only. A controller or model does not contain sentences meant for a human, with one tolerated exception: an exception message that a view will show verbatim (see the errors section in [reference/forms.md](reference/forms.md)).
- **Comments** are English and give context, never a transcript of the code (`code-style/index.md`, "Names").

## Authentication and permissions

- Login, logout, password reset, verification, "login as" (`/z/login-as/<userId>`) and the admin panel (`/z`) come from the framework. Never write a login, a key check, a token middleware, a session handler or password code: `User::add()`, `$user->updatePassword()`, `$user->verifyPassword()` (`docs/core-features/password-handling.md`). Users, roles, organizations and sessions go through the objects in `docs/core-features/access-control.md`, never through SQL on `z_user`, `z_role`, `z_organization` or `z_logintoken` outside seeds.
- Check permissions carefully, rather more than less, each at its level: the access check in the controller (`$req->checkPermission("area.action")` as the first statement), display decisions in the view (`$opt["user"]->checkPermission("x")`), row level access in the model (`userId = user()->userId` in the query). Some paths need all three, a public page needs none; what matters is that every access path is covered. The behaviour of `checkPermission`, its boolean form and `checkSuperPermission` are in [reference/controllers.md](reference/controllers.md).
- Roles and permissions are seeded in `app/Database/seed/accounts/0_permissions.sql` (the superadmin role gets `*.*`, each customer facing role a short dotted list), development and test users with fixed login tokens in `accounts/1_users.sql` ([reference/testing.md](reference/testing.md), "Logging in").
- Current user: `user()` helper in models and controllers, `$opt["user"]` in views. Fields: `userId`, `isLoggedIn`, `checkPermission()`.

## Stateless

A stack runs as several replicas in containers that are replaced all the time and have no volumes. Three rules follow, and they hold 100% of the time:

- **No PHP superglobals.** Never `$_POST`, `$_GET`, `$_FILES`, `$_COOKIE`, `$_SERVER`, `$_REQUEST`, `$_ENV`. The framework reads them once into the request object (`$req->getPost()`, `getGet()`, `getFile()`, `getCookie()`, `ip()`), which is what lets it route several requests inside one request; reading a superglobal bypasses that.
- **No session.** Never `$_SESSION`, `session_start()` or anything built on it. Nothing is kept in a session; the login session itself lives in the database (`z_logintoken`), and any state the next request needs goes into a table.
- **No local files.** A file on the container's disk is gone with the next deploy and invisible to the other replicas, so a file lives at most for the request that created it (the upload temp file handed to `AssetModel`). Everything that must survive the request goes to S3 or the database; `webroot/uploads` is the one tolerated local location and is not used either ([reference/files-s3.md](reference/files-s3.md)). If you think a task needs a local file, ask.

## Configuration

- Everything configurable lives in `z_config/z_settings.ini` as `key = value`. No comment lines (the parser treats `;` and `#` as part of the value). Keys are snake_case, grouped by prefix (`mail_*`, `s3_*`, `logger_*`).
- Every key can be overridden by `CONFIG_<KEY>` environment variables because `allow_env_config = true`. Secrets in production come from the environment, the ini holds development values.
- Read with `config("key")` everywhere, including model constructors. Never `getenv()`, `$_ENV` or `$booter->req->getBooterSettings()` in new code.
- `z_automated_setting.ini` is written by the framework and git ignored.
- Bump `assetVersion` whenever CSS or JS in `webroot/assets` changes, so `generateResourceLink` busts caches.
- `execution_type = test` marks development and CI: it switches on the Whoops error page with stack traces and the debug bar, so production runs `prod`. Code may skip external side effects (S3 delete, outgoing mail) only on that flag, never on a hostname check.
- `defaultIndex = DashboardController` picks the start page. No `Route::get("/")`.

## Dependencies and assets

- No CDN, for data privacy: a CDN request hands every visitor's IP address to a third party, which B2B projects cannot do without an explicit decision. Libraries, fonts (woff2 with `@font-face`), icon sets and CSS live in the repository under `webroot/assets` and are referenced through `generateResourceLink`.
- jQuery, Bootstrap 4.6, Font Awesome 6 with the v4 class names and Z.js ship with the framework: `$opt["layout_essentials_head"]` in the layout loads them through the asset proxy (`docs/core-features/asset-proxy.md`), so they are never copied into `webroot/assets`. Chart.js and DataTables complete the frontend toolkit. Nothing else without a reason written down in AGENTS.md.
- Composer: `zubzet/framework` plus very few packages for real complexity (`async-aws/s3`, `parsecsv/php-parsecsv`, `symfony/uid`, `php-amqplib/php-amqplib`). Never a package for something that is twenty lines of PHP.
- npm is for tooling only (Cypress, docker-compose scripts). No frontend build step, no SCSS, no TypeScript, no bundler: this is an explicit decision. With a really good reason you may ask; that is the most on offer.

## Every feature ships

What it needs: a migration when the schema changes, seed rows covering its states when it has data, and a Cypress spec when it can be tested, which is nearly always. Nothing is forced where there is nothing to ship, but if it can be tested, test it. `npm run tests` must stay green.

## Git, commits, repository docs

Defaults for stacks; a project's AGENTS.md may define its own commit style.

- Commit subject: gitmoji, space, English imperative, under about 70 characters; body only when the subject cannot carry it; ticket references at the end (`Closes #2786`, `refs #2759`). `✨ Add contact workspace filter`, `🐛 Fix delete button on requests page`, `💄 Make requests page work on mobile`, `⬆️ Update ZubZet to 1.3`, `🔧` config, `📝` docs, `✅` tests, `👷` CI, `🔒` security, `🎉` initial commit.
- Author is the configured git identity, signing as configured. No `Co-authored-by` or session trailers.
- Branches `feature/<topic>`, `fix/<topic>`, one topic each, merged into `main` through a pull request; `main` stays deployable and deploys by push (Drone or Forgejo Actions). Linear history: rebase onto `origin/main`, push rewritten branches with `--force-with-lease`. Do not touch pipeline files unless asked.
- `README.md`: name, one paragraph purpose, how to start (`npm run start`), how to test. `AGENTS.md`: project specifics an agent cannot infer (customer, deployment target, odd constraints, where the design comes from); it references this skill instead of repeating it. Both short.

## Before you call a feature done

- [ ] Every access path checks permissions; ownership is in the model query.
- [ ] Controller reads top to bottom: permission, load, actions, form, render. Under ~80 lines per action.
- [ ] Every SQL statement is in a model, parameterized, formatted as in `code-style/sql.md`, filters `active = 1` in WHERE and every JOIN, soft deletes; lists have an explicit `ORDER BY`.
- [ ] Input is collected by Z.Forms; buttons use `Z.Request.action`; nothing custom unless mirrored into a hidden Z.Forms field.
- [ ] View: `head` and `body` closures, one tag per line, Bootstrap only, no `style=`, no `<p>`, headings in order, `<?= e(...) ?>` for output, `json_encode` inside `<script>`, German dates via `strtotime`, inline `<script>` at the end.
- [ ] Every interactive element and status indicator has a `data-test`.
- [ ] New or edit flows are pages with their own URL; modals only for confirmations and tiny secondary forms.
- [ ] A schema change follows the migration template; seed rows cover each state the feature has.
- [ ] A Cypress spec covers the happy path of everything that can be tested; `npm run tests` is green.
- [ ] Every rule you had to bend is reported as a finding, and every rule you wanted to bend was asked about first.
- [ ] On a large feature, the conformance pass ran after the code was written, not only before.
- [ ] No open `TODO` that belongs to this feature, no commented-out code; small code smells met on the way are fixed in passing, a real refactor is its own task and asked for.
- [ ] Files go to S3 with private ACL and signed links; nothing user generated under `webroot/`.
- [ ] No CDN, no new helper file, no new global function, no new Composer package without a reason.
- [ ] Settings via `config()`, models via `model()`, current user via `user()`, logging via `logger()`, limits via `FILE_SIZE_*` / `TIMESPAN_*`; nothing rebuilt that the framework already has.
- [ ] No superglobal, no session, no file written to the local disk.
- [ ] `assetVersion` bumped if `webroot/assets` changed.
- [ ] Commit follows the project's style.

## Old patterns

Projects on older framework versions or older templates still contain these. Keep them consistent inside such a project, do not introduce them in new code.

- `z_models/` and `z_controllers/` directories instead of `app/Models` and `app/Controllers`.
- `$req->getModel()`, `$req->getRequestingUser()` and `$booter->req->getBooterSettings()` instead of `model()`, `user()` and `config()`.
- Copies of jQuery, Bootstrap and Z.js in `webroot/assets/js` (the asset proxy serves them since framework 1.2.0).
