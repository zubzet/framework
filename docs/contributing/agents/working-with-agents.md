# Working With Agents

This page is for AI coding agents (Claude Code, Cursor, Codex, Aider, etc.) and contributors who need a deeper map of the framework internals than [How To Contribute](../how-to-contribute.md) provides. If you're getting started, read that first.

## Repository layout

| Path | What lives there |
| ---- | ---------------- |
| `src/` | Framework source — Composer-loaded into every ZubZet project |
| `src/IncludedComponents/` | Bundled controllers, models, views, routes, and migrations the framework ships |
| `docs/` | MkDocs-rendered documentation (this site) |
| `tests/e2e/` | Cypress end-to-end test suite running the dockerized app |
| `tests/e2e/modules/` | The two sample modules (`guestbook`, `theme`) permanently installed in the e2e app |
| `mkdocs.yml` | Docs site nav and theme config |
| `composer.json` | PHP 8.0–8.5 support, autoload, dependencies |

There is no top-level `package.json` and no PHPUnit suite — testing is end-to-end only, run from `tests/e2e/`.

`src/` subdirectories at a glance:

- `Authentication/` — `User`, `Session`, `Permission/`, `PasswordHash/` (native Argon2id, self-describing schemes, rehash-on-login), role and group handling
- `Bootstrap/` — `Configuration` trait that parses `z_settings.ini`
- `Console/` — CLI application bootstrap, `run` action bridge, `ActionDiscovery` and `CommandDiscovery`
- `Core/` — Foundation traits (`CanRetrieveModel`, `CanRetrieveBooterSettings`, `Constants`, `FunctionConflictResolution`)
- `Database/` — `Connection`, prepared-statement `Interaction`, migration commands
- `ErrorHandling/` — `ExceptionBehavior`, `WhoopsHandler`, `BehaviorOption`, `DebugBar/` (bridge, collectors, traits)
- `Form/` — Validation rules (`required`, `unique`, `exists`, `length`, …)
- `Logger/` — `LoggerFactory`, channels, slow-request logging
- `Maintenance/` — Standalone maintenance gate (see [Maintenance Mode](../../core-features/maintenance.md))
- `Message/` — `Request`, `Response`, `Input/State`
- `QueryBuilder/` — CakePHP query-builder adapter
- `Registry/` — Central resolver for convention lookups + module discovery (see below)
- `Resources/` — Asset proxy: ordered `Mount` chain, bundled Composer packages
- `Routing/` — `Router` trait, FastRoute integration, `Route` builder
- `Support/` — Global helpers, dynamic attributes, function-conflict resolution
- `Testing/` — Coverage commands

## Bootstrap order

`src/ZubZet.php __construct` is sensitive to ordering. The current sequence:

```php
self::$instance = $this;           // zubzet() resolves to this
new GlobalReferences;              // defines config(), logger(), isCli(), …
new Constants;                     // TIMESPAN_DAY_1 etc.
$this->loadConfiguration(...);     // populates settings from z_settings.ini

MaintenanceHandler::gate();        // exits early if maintenance is active

LoggerFactory::handleSlowRequest();
$this->setExceptionBehavior();
$this->assetProxy = new AssetProxy;
new Helpers;
$this->setRequestResponse(...);
$this->z_db = new Connection;
$this->user = new User;
```

A few inter-phase dependencies that are not obvious:

- The `config()` helper resolves through `zubzet()`, which requires both `self::$instance = $this` and `new GlobalReferences` to have run. Anything calling `config()` must be after both.
- `Configuration` (the trait at `src/Bootstrap/Configuration.php`) is genuinely self-contained — no DB, no logger, no instance lookups. Safe to invoke before any other subsystem.
- `setExceptionBehavior()` reads `$this->showErrors`, which `loadConfiguration` populates. Calling them in the wrong order means Whoops never installs.
- `LoggerFactory::handleSlowRequest()` just calls `register_shutdown_function(…)`. PHP fires shutdown handlers even after `exit;`, so anything that exits before this point cleanly bypasses logger side-effects. `MaintenanceHandler::gate()` relies on this — a maintenance hit performs zero DB writes and no log writes.
- `BehaviorOption` levels: `0` = NONE, `1` = EXCEPTIONS, `2` = ALL (`src/ErrorHandling/BehaviorOption.php`).

## Routing & MVC

ZubZet uses convention-based routing with FastRoute as an opt-in override. The path `["dashboard", "stats"]` maps to `DashboardController->action_stats($req, $res)`. Default action is `action_index`; missing methods fall to `action_fallback`. Controller and model files resolve through the Registry (see below): flat lookup first, then recursively into subdirectories by bare name, across userspace, modules, and framework roots. Model dot notation (`getModel("reports.Stats")`) addresses an exact sub-path and never falls back to the recursive index. See [MVC](../../core-features/mvc.md), [Controllers and Actions](../../core-features/controllers-and-actions.md), and [Routing](../../core-features/routing.md).

A view is a Blade template that extends a layout and defines head/content sections:

```blade
@extends($layout)

@section("head")
    <link rel="stylesheet" href="...">
@endsection

@section("content")
    <h1><?= $opt["title"] ?></h1>
@endsection
```

Rendered via `$res->render("path/to/view", $vars, "layout/…")` or the `view()` global helper.

## Registry & modules

`src/Registry/` is the single resolver for everything the framework loads by convention
(controllers, models, views, routes, commands, migrations, seeds, assets). The API is three static
calls on `Registry`: `paths($kind)` (ordered roots), `files($kind)` (every file across roots), and
`find($kind, $name)` (locate one file). Precedence everywhere: userspace, then modules in order,
then framework. Asset-proxy mounts follow the same order (modules before the framework layers);
the application's webroot needs no mount because the web server serves it before PHP runs.

`find()` memoizes per request in `StaticCache` and probes each root flat first; the recursive
per-root index (`RootIndex`) only runs for bare names after a flat miss, shallowest match first.
Do not add new `file_exists`/`glob` lookups at call sites: route them through the Registry.

Module discovery lives in `Registry\Modules`: installed Composer packages of type `zubzet-module`,
ordered by the `modules` ini key (comma-separated package names), unlisted ones after in Composer
installed order, individually deactivatable via `modules_disabled`. Convention console commands
(`app/Commands/`, userspace and modules) are discovered by `Console\CommandDiscovery` and added in
reverse precedence order because Symfony resolves command-name collisions last-add-wins: the
userspace registration lands last and wins. The sample modules are
`tests/e2e/modules/{guestbook,theme}`, exercised by the specs in
`tests/e2e/tests/cypress/e2e/modules/`. User-facing docs: [Modules](../../advanced-features/modules.md);
internals and rationale: [Module System Architecture](../module-system-architecture.md).

## Render engine (Katana)

Views render through [Katana](https://github.com/katanaphp/blade), a standalone Blade compiler. It is Blade only, with no closure fallback. The user-facing reference is [Views](../../core-features/views.md).

The only Katana-specific glue is `src/Rendering/Katana/`:

- `Engine.php` is the adapter. It registers the view roots from `Registry::paths("views")` as an ordered finder chain (userspace `z_views` first, then module view roots in module order, then framework `IncludedComponents/views`), so a name resolves by precedence and `@extends` / `@include` / components all resolve across every root. It builds a fresh `Blade` per render so `@section` state cannot leak between renders, registers the framework essentials under the `zubzet` component namespace (`<x-zubzet::head/>` and `<x-zubzet::body/>`, which an app component can neither shadow nor be shadowed by), and hands the layout name over as the `$layout` render datum.
- `Hooks.php` binds request-scoped directives, currently `@auth` and `@guest`. The argument is a framework permission (dotted, wildcard aware), and `@guest` is the negation. This is where future framework directives go.

`src/Rendering/CanRenderView.php` builds the `$opt` contract, logs the render and feeds the debug bar, then renders through the engine. A missing view or layout is caught (`BladeException`) and re-rendered as the framework 500 page in the guaranteed `layout/min_layout`. View names resolve by name (a legacy `.php` or `.blade.php` extension is stripped; dot and slash notation both work), and the compiled-view cache directory is keyed on the installed engine reference so an engine upgrade starts from a clean cache. `e()` (in `src/Support/Helpers.php`) delegates to `\Blade\e()`, so it escapes identically to `{{ }}`.

## Global helpers

Defined in `src/Support/GlobalReferences.php`, all wrapped with `FunctionConflictResolution::requireAndThen` so they can't be redeclared:

| Helper | Returns |
| ------ | ------- |
| `zubzet()` | `ZubZet` singleton |
| `request()` | Current `Request` |
| `response()` | Current `Response` |
| `config($key=null, $useDefault=true, $default=null)` | Booter setting value, or array of all settings |
| `user()` | `User` (currently logged-in) |
| `db($connection="default")` | `Connection` |
| `model($name, $dir=null)` | Model instance |
| `view($document, $opt=[], $options=[])` | Renders via response |
| `logger($name=null)` | `Logger` (default: `app` channel) |
| `isCli()` | `php_sapi_name() === "cli"` |

See [Global Helper Functions](../../core-features/global-helper-functions.md).

## Testing

The full e2e suite lives in `tests/e2e/`. Run it from there:

```bash
cd tests/e2e

# Bring up the docker stack (~2 min first time)
npm run start

# Run the full suite headless (~9 min, 760+ tests)
npm run tests

# Just the module-system group
npm run tests -- --spec 'tests/cypress/e2e/modules/**/*.cy.js'

# Run one spec
npm run tests -- --spec 'tests/cypress/e2e/core/maintenance.cy.js'

# Open Cypress UI
npm run cypress

# Tear down
npm run stop
```

The dockerized app is served on `http://localhost:8080`. The `host` value in `tests/e2e/z_config/z_settings.ini` says `:4000` — that's the configured base URL, not what Apache exposes. Always hit `:8080` for manual checks.

Useful Cypress helpers in `tests/e2e/tests/cypress/support/commands.js`:

| Command | Purpose |
| ------- | ------- |
| `cy.query(testid)` | Select by `[data-test=…]` |
| `cy.fillForm(inputs)` | Bulk-fill a form |
| `cy.loginAs(profile)` | Set session token from `fixtures/logins.json` |
| `cy.setConfigSetting(key, value)` | Patch `tests/e2e/z_config/z_settings.ini` |
| `cy.saveConfigBackup()` / `cy.restoreConfigBackup()` | Wrap suites that mutate config |
| `cy.dbSeed()` | `npm run seed` + clear sessions |
| `cy.http(method, endpoint, body, callback)` | API call with `X-API-KEY: 1234` |

To exercise a CLI command end-to-end:

```js
cy.exec('docker exec application php index.php info:startup', {
    failOnNonZeroExit: false
}).then((result) => {
    expect(result.exitCode).to.equal(0);
});
```

Manual verification against a config-dependent path:

```bash
cp tests/e2e/z_config/z_settings.ini /tmp/zsettings.bak
sed -i 's/^maintenance_mode = .*/maintenance_mode = enabled/' tests/e2e/z_config/z_settings.ini
curl -s -o /tmp/page -w 'HTTP %{http_code}\n' http://localhost:8080/
cp /tmp/zsettings.bak tests/e2e/z_config/z_settings.ini
```

Occasional flake: a single failing run sometimes recovers on re-run. Re-run once before debugging.

## Debug bar

User-facing docs: [Debug Bar](../../core-features/debug-bar.md).

All debug-bar code lives in `src/ErrorHandling/DebugBar/`:

- `DebugBarBridge.php` is the only static entry point. It bootstraps `StandardDebugBar`, registers collectors, wires the asset proxy, and exposes `renderHead()` / `renderBody()` for the layout. It returns early when `execution_type !== "test"`.
- `Collectors/` holds the framework's own collectors (`QueryCollector`, `TemplateCollector`, `MonologCollector`).
- `CanCollect` is a trait used by the bridge to expose strongly typed `collectQuery()` / `collectTemplate()` / `collectLogger()` static helpers that forward into the right collector.
- `CanFormatValue` is a small trait that normalizes scalars/arrays/objects into a readable string for display.

Call sites that feed the bar:

| Source | Trait/Helper | Collector |
| ------ | ------------ | --------- |
| `Connection::exec` | `DebugBarBridge::collectQuery(...)` | `QueryCollector` |
| `CanRenderView::render` | `DebugBarBridge::collectTemplate(...)` | `TemplateCollector` |
| `LoggerFactory::getOrCreateLogger` | `DebugBarBridge::collectLogger(...)` | `MonologCollector` (a Monolog handler) |
| `Registry::find` / `Registry::files` | `DebugBarBridge::collectResolution(...)` | `ResolutionCollector` (lookup provenance: userspace / module / framework) |

Internal-query filtering uses source tagging: models that mark themselves with the `IsInternalModel` trait set `isInternalModel = true`, and `Connection::exec` records the calling model on the connection before the query runs. `QueryCollector::addQuery` then drops queries from internal models when `debugbar_hide_internal_queries = true` (default). Direct `db()->exec(...)` calls have no calling model and are always shown.

To add a new collector, implement `DebugBar\DataCollector\DataCollector` (or extend an existing one), register it in `DebugBarBridge::bootstrap()`, and expose a `collectXxx()` method on `CanCollect` that forwards via `self::collect("name", "addXxx", func_get_args())`.

## Console commands

Run commands inside the application container:

```bash
docker exec application php index.php <command>
```

| Command | What it does |
| ------- | ------------ |
| `db:migrate` | Run pending migrations |
| `db:seed` | Drop and re-seed the database |
| `db:sync` | Sync migration state up to a version/date |
| `db:status` | Show migration status |
| `db:unlock-migration` | Release a stuck migration lock |
| `info:startup` | Print framework startup banner (no side effects — safe in tests) |
| `module:setup` | Append-only merge of missing module ini defaults into the app ini |
| `testing:coverage:start` / `:stop` | Bracket a coverage session |

Convention commands from `app/Commands/` (userspace and modules) appear next to these in `list`;
each is a global class named like its file, extending the Symfony `Command` class. On a
command-name collision the userspace copy wins, then modules in order, then the framework.

See [Console Commands](../../core-features/console-commands.md) for full flags.

## Commit & PR conventions

- **Conventional commits with a scope**: `feat(admin): …`, `fix(layout): …`, `refactor(maintenance): …`, `test(...)`, `docs(...)`. See [How To Contribute](../how-to-contribute.md) for the migration from Gitmoji.
- **Atomic commits.** Split work by scope. Example: a feature touching code + tests + docs becomes `refactor(...)`, `feat(...)`, `test(...)`, `docs(...)` — four commits, one scope each. Combining (`feat+test`) is not the project style.
- **One-line messages, no `Co-Authored-By` trailer.**
- **PR base is `develop`.** Feature work merges into `develop`; `develop` is later promoted to `main` via a separate PR. Verify with `gh pr view <n> --json baseRefName` if unsure; some tooling surfaces stale branch names. See [How To Contribute → Branching model](../how-to-contribute.md#branching-model).
- CI runs e2e on PHP 8.0 and 8.5 for PRs and feature-branch pushes (the version-edge smoke). Pushes to `develop`, `main`, and version tags run the full matrix (8.0–8.5). Watch with `gh pr checks <n> --repo zubzet/framework --watch`.

## Working style for AI agents

- **Never commit or push without an explicit ask.** This is critical framework code. AI output is reviewed by hand before it lands — leave changes uncommitted in the working tree (or staged, if helpful) and wait. Even after a successful test run, do not run `git commit`, `git push`, or `gh pr` write actions unless the maintainer asks for them in that turn.
- **Iterative pace.** Make small changes, run tests, report concisely, wait. Don't pre-build large structures unless asked.
- **Watch for parallel edits.** A `<system-reminder>` notice that a file was modified means re-read it before any further change — never assume your in-context view is current.
- **"Any other ideas?"** is a request for 3–4 ranked options with trade-offs and a recommendation. Don't implement until asked.
- **"Make a useful decision."** Decide. State trade-offs in 1–2 lines, implement.
- **Inline aggressively.** When a private function has only one caller, inlining is the project default. Drop dead code and unused parameters confidently.
- **Run the full e2e suite after any framework-internals change.** Three minutes catches the kind of subtle ordering bugs that bootstrap-adjacent changes cause.

## Migrations

Framework migrations live in `src/IncludedComponents/database/Migration/` and ship with the framework. Project migrations live in `app/Database/migrations`; module migrations join the run as part of the external set (like the framework's). Executed state is keyed on the basename, so `db:migrate`/`db:sync` abort on duplicate basenames anywhere in the assembled set: prefix module migration filenames with the module name. See [Migrations](../../core-features/migrations/index.md) for the file/filename conventions and CLI commands.

**Bundled migrations must be idempotent.** They may already be partially applied on consumer projects (manual schema work, partial sync state, replays after a recovery) and re-running must not fail. Concretely:

- `CREATE TABLE IF NOT EXISTS …`
- `ALTER TABLE … ADD COLUMN IF NOT EXISTS …`
- `ALTER TABLE … ADD INDEX IF NOT EXISTS …` (and the `DROP` variants)
- `INSERT … ON DUPLICATE KEY UPDATE` or guarded with `WHERE NOT EXISTS`

The `z_version` table prevents re-execution under normal flow, but the rule still applies — a migration that fails on second run is a bug.

## Common pitfalls

- **Port confusion.** App is at `:8080`, not `:4000`. The `host` setting in INI is informational, not the listening port.
- **No PHPUnit / unit tests.** All testing is Cypress e2e in `tests/e2e/`.
- **Multiple PHP versions in CI.** Don't rely on a feature available only in PHP 8.4+ without checking the matrix.
- **`config()` is unavailable before bootstrap.** Anything called from `MaintenanceHandler::gate()` must already have configuration loaded; anything earlier must read INI directly.
- **Cypress flake.** Re-run a failing suite once before opening an issue, try to fix the flakiness if possible.
- **The sample modules are part of the e2e app.** `tests/e2e/modules/{guestbook,theme}` are installed on every run; a red `modules/` spec usually means a Registry or precedence regression, not a spec problem. Never "fix" one by renaming sample files to dodge a collision.
- **Convention classes are global.** Controllers, models, and commands from userspace and modules share one global class namespace; the first include wins and `class_exists` guards prevent redeclaration fatals. Name module classes with a module prefix unless shadowing is intended.
