# Working With Agents

This page is for AI coding agents (Claude Code, Cursor, Codex, Aider, etc.) and contributors who need a deeper map of the framework internals than [How To Contribute](../how-to-contribute.md) provides. If you're getting started, read that first.

!!! danger "Stop before publishing — always ask first"
    Do **not** run `git commit`, `git push`, `gh pr create`, or any other action that publishes changes outward, unless the maintainer has explicitly asked for that step in the current turn. A green test run is **not** authorization. Leave changes in the working tree (staged is fine) and wait. This rule applies even when an earlier turn authorized a commit — authorization is per-action, not blanket. See [Working style for AI agents](#working-style-for-ai-agents) for the rest of the boundaries.

## Repository layout

| Path | What lives there |
| ---- | ---------------- |
| `src/` | Framework source — Composer-loaded into every ZubZet project |
| `src/IncludedComponents/` | Bundled controllers, models, views, routes, and migrations the framework ships |
| `docs/` | MkDocs-rendered documentation (this site) |
| `tests/e2e/` | Cypress end-to-end test suite running the dockerized app |
| `web/` | Static assets served via `AssetProxy` — currently just `Z.js` (frontend runtime) |
| `mkdocs.yml` | Docs site nav and theme config |
| `composer.json` | PHP 8.0–8.5 support, autoload, dependencies |
| `jsconfig.json` | Scopes the JS language server to `web/**/*.js` so `Z`, `ZForm`, `ZFormField`, `ZCED`, `ZCEDItem` resolve as globals (autocomplete inside `<script>` blocks of views) |

There is no top-level `package.json` and no PHPUnit suite — testing is end-to-end only, run from `tests/e2e/`.

`src/` subdirectories at a glance:

- `Authentication/` — `User`, `Session`, `Permission/`, role and group handling
- `Bootstrap/` — `Configuration` trait that parses `z_settings.ini`
- `Core/` — Foundation traits (`CanRetrieveModel`, `CanRetrieveBooterSettings`, `Constants`, `FunctionConflictResolution`)
- `Database/` — `Connection`, prepared-statement `Interaction`, migration commands
- `ErrorHandling/` — `ExceptionBehavior`, `WhoopsHandler`, `BehaviorOption`, `DebugBar/` (bridge, collectors, traits)
- `Form/` — Validation rules (`required`, `unique`, `exists`, `length`, …)
- `Logger/` — `LoggerFactory`, channels, slow-request logging
- `Maintenance/` — Standalone maintenance gate (see [Maintenance Mode](../../core-features/maintenance.md))
- `Message/` — `Request`, `Response`, `Input/State`
- `QueryBuilder/` — CakePHP query-builder adapter
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

ZubZet uses convention-based routing with FastRoute as an opt-in override. The path `["dashboard", "stats"]` maps to `DashboardController->action_stats($req, $res)`. Default action is `action_index`; missing methods fall to `action_fallback`. See [MVC](../../core-features/mvc.md), [Controllers and Actions](../../core-features/controllers-and-actions.md), and [Routing](../../core-features/routing.md).

A view file returns an associative array of head/body closures:

```php
<?php return [
    "head" => function($opt) { ?>
        <link rel="stylesheet" href="...">
    <?php },
    "body" => function($opt) { ?>
        <h1><?= $opt["title"] ?></h1>
    <?php }
]; ?>
```

Rendered via `$res->render("path/to/view.php", $vars, "layout/…")` or the `view()` global helper.

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

# Run the full suite headless (~3 min, 300+ tests)
npm run tests

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
| `testing:coverage:start` / `:stop` | Bracket a coverage session |

See [Console Commands](../../core-features/console-commands.md) for full flags.

## Commit & PR conventions

- **Conventional commits with a scope**: `feat(admin): …`, `fix(layout): …`, `refactor(maintenance): …`, `test(...)`, `docs(...)`. See [How To Contribute](../how-to-contribute.md) for the migration from Gitmoji.
- **Atomic commits.** Split work by scope. Example: a feature touching code + tests + docs becomes `refactor(...)`, `feat(...)`, `test(...)`, `docs(...)` — four commits, one scope each. Combining (`feat+test`) is not the project style.
- **One-line messages, no `Co-Authored-By` trailer.**
- **PR base is `develop`.** Feature work merges into `develop`; `develop` is later promoted to `main` via a separate PR. Verify with `gh pr view <n> --json baseRefName` if unsure; some tooling surfaces stale branch names. See [How To Contribute → Branching model](../how-to-contribute.md#branching-model).
- CI runs e2e on PHP 8.0 and 8.5 for PRs and feature-branch pushes (the version-edge smoke). Pushes to `develop`, `main`, and version tags run the full matrix (8.0–8.5). Watch with `gh pr checks <n> --repo zubzet/framework --watch`.

## Working style for AI agents

- **Never commit, push, or open a PR without an explicit ask.** This is critical framework code. AI output is reviewed by hand before it lands — leave changes uncommitted in the working tree (or staged, if helpful) and wait. Even after a successful test run, do not run `git commit`, `git push`, `gh pr create`, or any other publishing action unless the maintainer asks for it in that turn. Authorization is **per-action**: "go ahead and commit" does not authorize a push; a push does not authorize a PR. When in doubt, stop and ask.
- **Iterative pace.** Make small changes, run tests, report concisely, wait. Don't pre-build large structures unless asked.
- **Watch for parallel edits.** A `<system-reminder>` notice that a file was modified means re-read it before any further change — never assume your in-context view is current.
- **"Any other ideas?"** is a request for 3–4 ranked options with trade-offs and a recommendation. Don't implement until asked.
- **"Make a useful decision."** Decide. State trade-offs in 1–2 lines, implement.
- **Inline aggressively.** When a private function has only one caller, inlining is the project default. Drop dead code and unused parameters confidently.
- **Run the full e2e suite after any framework-internals change.** Three minutes catches the kind of subtle ordering bugs that bootstrap-adjacent changes cause.

## Migrations

Framework migrations live in `src/IncludedComponents/database/Migration/` and ship with the framework. Project migrations live in `app/Database/migrations`. See [Migrations](../../core-features/migrations/index.md) for the file/filename conventions and CLI commands.

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
