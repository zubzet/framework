# Module System Architecture

This page documents the internals of the module system for framework maintainers: why the
`Registry` exists, how resolution works, how each call site consumes it, and which trade-offs were
made deliberately. If you want to *use* or *write* a module, read the user-facing
[Modules](../advanced-features/modules.md) page instead; nothing there is repeated here.

## Motivation and history

The framework grew around one recurring idea: look in the application first, fall back to the
framework. Before version 1.4.0 that idea was implemented five times, with three different
mechanisms and even two different orderings:

1. **Controllers** (`src/Routing/Router.php`): a chain of two `file_exists` probes, userspace
   `z_controllers` first, then `IncludedComponents/controllers/`.
1. **Routes** (`src/Routing/Router.php`, `getRouteDispatcher()`): two `glob("*.php")` calls merged
   into one `require_once` loop, userspace `routes` dir first, framework second.
1. **Models** (`src/Core/CanRetrieveModel.php`): `file_exists` probes against `z_models`, falling
   back to `IncludedComponents/models/`.
1. **Views** (`src/Rendering/Katana/Engine.php`): a hardcoded two-element ordered finder chain,
   reversed wholesale by `$prioritizeFrameworkViews`.
1. **Assets** (`src/Resources/AssetProxy.php`): a first-match mount list, and the one place with
   the *opposite* ordering: framework assets first, then `z_frontend_root`, then bundled assets.

On top of that, the migration and seed commands (`src/Database/Migration/Commands/{Migrate,Sync,Seed}.php`)
hardcoded their roots (`./app/Database/migrations`, `IncludedComponents/database/Migration`,
`./app/Database/seed`), and `z_migrationModel::getFiles()` carried the comment
`// @TODO: abstract RecursiveIteratorIterator`, an early acknowledgement that recursive file
enumeration wanted a shared home.

A module system turns the two-layer lookup into a three-layer one, in seven places at once.
Patching a module loop into five independent implementations would have multiplied the existing
divergence, so the pattern was abstracted exactly once instead: every convention lookup now routes
through `src/Registry/`.

## The Registry model

The observation behind the design: controllers, models, views, routes, migrations, seeds, and
assets look like different features but are literally the same problem. The Registry therefore
answers exactly three questions, as three static calls on `Registry`:

1. `paths($kind)`: where can this kind of thing live? Returns the ordered roots.
1. `files($kind)`: give me all files of this kind, across all roots, in precedence order.
1. `find($kind, $name)`: locate one specific file.

`moduleRoots($kind)` is a deliberate fourth accessor for the two call sites that must *not* get
the standard ordering: assets (historical framework-first order) and migrations (modules are a
distinct set, not a precedence layer).

### Kinds

A `Kind` (`src/Registry/Kind.php`) is a value object describing where one kind lives in userspace,
inside a module, and inside the framework. The built-in table lives in `src/Registry/Kinds.php`:

| Kind | Userspace root | Module sub-path | Framework sub-path | Extensions | deepFind | deepFiles |
| ---- | -------------- | --------------- | ------------------ | ---------- | -------- | --------- |
| controllers | config `z_controllers` | `app/Controllers` | `IncludedComponents/controllers/` | `.php` | yes | yes |
| models | config `z_models` | `app/Models` | `IncludedComponents/models/` | `.php` | yes | yes |
| views | config `z_views` | `app/Views` | `IncludedComponents/views` | `.php` | yes | yes |
| routes | config `routes` | `app/Routes` | `IncludedComponents/routes` | `.php` | yes | **no** |
| commands | config `z_commands` | `app/Commands` | none (framework commands register explicitly) | `.php` | yes | yes |
| migrations | fixed `./app/Database/migrations` | `app/Database/migrations` | `IncludedComponents/database/Migration` | `.sql`, `.php` | yes | yes |
| seeds | fixed `./app/Database/seed` | `app/Database/seed` | none (framework ships no seeds) | `.sql`, `.php` | yes | yes |
| assets | config `z_frontend_root` | `webroot` | `IncludedComponents/assets/` | any | yes | yes |

Not every column is consumed for every kind: views are consumed as directory roots only (Katana
does the per-name lookup), and assets are consumed via `moduleRoots()` only. `Kinds::register()`
is the seam for future kinds; nothing uses it yet.

### Module discovery and ordering

`src/Registry/Modules.php` is the only place that knows what a module *is*: an installed Composer
package of type `zubzet-module`, discovered through `InstalledVersions` (an in-memory array read,
zero syscalls). Ordering: packages listed in the `modules` ini key first, in listed order, then
unlisted installed modules in Composer installed order. Listed-but-not-installed names are ignored,
so a stale ini entry can affect ordering but never discovery. Flat ini config was chosen for the
ordering key because that is where this framework already keeps such decisions; no
`Configuration.php` change was needed, the flat parser accepts arbitrary keys. Metapackages
(`getInstallPath()` returning null) are skipped. Both `packages()` and `roots()` are memoized in
`StaticCache` for the request.

### RootIndex

`src/Registry/RootIndex.php` is the lazy recursive filename index of one root: a
`RecursiveIteratorIterator` sweep producing `bare name => sorted relative paths`, memoized per
`(root, extensions)` in `StaticCache`. It is the abstraction the `@TODO` in `z_migrationModel`
asked for, and it exists only on the slow path (see below). Recursive lookup overall was a
small-code big-impact choice: a few lines here let users organize controllers and models in
subdirectories, while the flat-first fast path keeps every existing lookup byte-identical and
cost-identical.

## Resolution semantics

One global precedence rule, everywhere: **userspace first, then modules in module order, then the
framework**. The single documented exception is the asset proxy (below).

`find($kind, $name)` walks the roots in precedence order and, per root:

1. **Flat fast path**: `file_exists($root . $name . $extension)` for each extension. This is
   byte-for-byte the historical lookup; a name that resolved before the Registry resolves to the
   same file at the same cost.
1. **Recursive fallback**: only for bare names, only for `deepFind` kinds, and only after the flat
   probe missed in this root, the `RootIndex` is consulted. The first root with any match wins.

Within a root the index returns the **shallowest** match first, ties broken by `strcmp` on the
relative path. Both halves of that rule are load-bearing: raw `readdir` order is
filesystem-dependent, so without the byte-order tie-break resolution would differ between machines
and CI; and shallowest-first guarantees that adding a nested file can never silently steal an
existing top-level lookup, the flat file remains the canonical one.

Names containing a path separator (model dot notation arrives as `reports/FooModel`) are explicit
paths: they address one exact location per root and never fall back to the index. Recursion is a
convenience for bare names, not an alternate addressing scheme.

`find()` results are memoized per request in `StaticCache` under `(kind, name)`. This is a
correctness feature, not an optimization: the Router reroutes to `executePath(["error", ...])` on
failures, and one class name must map to exactly one file for the whole request, including those
re-entries. Otherwise an error-path re-resolution could pick a *different* same-named file and
`include` it, producing a redeclaration fatal inside the error handler. The include sites in
`Router.php` and `CanRetrieveModel.php` additionally guard with `class_exists($name, false)`:
first loaded wins for the request, never a PHP fatal.

## Per-kind wiring

### Views (`src/Rendering/Katana/Engine.php`)

`Registry::paths("views")` feeds Katana's ordered finder chain, so the entry view, `@extends`,
`@include`, and components all resolve userspace, then modules, then framework.
`$prioritizeFrameworkViews` is now "move the framework root to the front"; with zero modules that
is exactly the old two-element `array_reverse`. The flag remains an `@internal` escape hatch
scheduled for removal.

### Controllers (`src/Routing/Router.php`)

`executeControllerAction()` resolves via `Registry::find("controllers", $controller)`; a null
result is the unchanged 404 fallback. The controller name is pre-sanitized by the router's
replacement table before it reaches the Registry, so no traversal input arrives here. The
`class_exists` guard covers the reroute scenario described above.

### Models (`src/Core/CanRetrieveModel.php`)

The explicit `$dir` override branch is legacy behavior, byte-identical. The default branch is
`Registry::find("models", $model)`; dot notation arrives as an explicit sub-path and therefore
probes exact locations per root, exactly the historical two probes with module roots in between.
The instance cache key changed from the bare model name to the **resolved file path**, so equally
named models from different roots can never alias to the wrong instance (this also fixed a
pre-existing `$dir` aliasing bug; noted in `Changelog.md`).

### Routes (`src/Routing/Router.php`)

`getRouteDispatcher()` requires every file from `Registry::files("routes")`: userspace files, then
module files in module order, then framework files. The kind sets `deepFiles: false`, so per-root
enumeration is the same flat alphabetical `glob("*.php")` as before; nested route files stay
unloaded. Duplicate route patterns are a **hard error**: FastRoute throws instead of silently
preferring one registration, so a module can never take over an existing URL unnoticed. The
`/_zubzet` prefix is reserved for the framework.

### Migrations (`Migrate.php`, `Sync.php`)

Module migrations join the **external** set alongside the framework's own: excluded by
`--exclude-external`, exempt from the skipped-timeline check unless `--enforce-external-timeline`
(a freshly installed module always carries files dated in the past). The userspace root keeps the
literal `"./app/Database/migrations"` string (specs assert it), and module/framework roots always
pass `$setupPathIfNotExists = false` to `getFiles()` because vendor may be read-only. Because
`z_version` keys executed state on the file's **basename**, equal basenames across roots would
silently swallow one migration; the import therefore aborts with a hard error listing all sources
when the assembled set contains a duplicate basename. The convention (module-prefixed filenames)
is documented, not enforced beyond that guard.

### Seeds (`Seed.php`)

`db:seed` iterates `Registry::paths("seeds")`: userspace first, then modules in order (the
framework ships none, `frameworkSubPath` is null). `filterSeedFiles()` runs **per root**, so the
`-e`/`-i` environment selectors stay root-relative and apply inside the application and inside
every module alike.

### Assets (`src/Resources/AssetProxy.php`)

Module `webroot/` directories mount **before** the framework's sources (framework assets,
`z_frontend_root`, bundled packages), aligning the proxy with the global precedence: modules can
shadow framework assets, earlier modules win over later ones. The application's own `webroot/`
needs no mount because the web server serves it before PHP runs, which keeps userspace at the top
of the chain. As defense-in-depth, `serve()` denies the `.php`, `.phtml`, and `.ini` extensions
on every mount and turns `Mount::resolve()` traversal exceptions into a 404. Only
`<moduleRoot>/webroot` is ever mounted, never the module root.

### Commands (`Application.php`, `CommandDiscovery.php`)

Convention commands live in `app/Commands/` (userspace and modules; the framework's own commands
stay explicitly registered as namespaced classes, so the kind's framework root is null). A command
file declares one global class named like the file, extending the Symfony `Command` class.
`CommandDiscovery::commands()` walks `Registry::files("commands")` in precedence order with the
usual `class_exists` include guard, so a same-named class from an earlier root shadows a later
file. Symfony resolves command-**name** collisions last-add-wins, therefore `Application.php` adds
the framework commands first and the discovered commands in **reverse** precedence order: the
userspace registration lands last and overrides any module or framework command sharing its name.

### Console (`Application.php`, `RunCommand.php`, `Startup.php`)

`Application.php` registers `module:setup` alongside the discovered convention commands.
`RunCommand` unions `ActionDiscovery::find()` over the userspace controller root plus
`moduleRoots("controllers")` using `+=`, so userspace shadows modules in CLI listings.
`info:startup` prints one row per discovered module in resolution order, which makes a typo'd
package type or `modules` entry diagnosable in one place.

## Performance model

Performance was treated with measurement, not guessing: the primitives were benchmarked before
implementation against a simulated 1 userspace + 30 modules + 1 framework root set.

| Operation | Cost |
| --------- | ---- |
| Direct hit in userspace (the common case, any module count) | 2 µs, unchanged |
| One bare-name lookup missing all 32 roots | ~50 µs |
| Recursive index of one root (slow path, once per root per request) | 0.3 to 0.7 ms |
| Worst realistic request (6 miss chains + 32 route globs) | ~0.56 ms |

Thirty modules add roughly 0.3 to 0.6 milliseconds of filesystem work per request, noise against
typical request time. That is why there is **no disk cache in v1**: it would add a stale-index bug
class after deploys for a negligible win at realistic module counts. Two future levers are named
in the code for when a real app needs them: persisting `RootIndex` under the `zubzet_cache`
directory (the seam `Engine::cachePath()` already documents), and switching FastRoute to
`cachedDispatcher` (the per-request route glob + require is the largest per-module constant).

## Backward-compatibility contract

With zero modules installed, resolution **results and syscall counts are identical** to the
pre-Registry framework. This was a hard constraint (external consumer apps): the flat probe runs
first, recursion fires only after an all-roots flat miss, `deepFiles: false` keeps the route file
set and order byte-identical, and module discovery costs no syscalls.

Two changes are intentionally additive and documented in `Changelog.md` as migrator notes:

1. Bare-name lookups that previously 404'd or threw can now resolve to nested files. Nothing that
   resolved before resolves differently; stale nested copies become reachable and should be
   deleted.
1. The model cache key is now the resolved path; `$dir`-parameter call sites that aliased one
   instance now get separate instances.

## Known limitations and deferred work

1. `z_version` migration names are not root-qualified; v1 only detects basename collisions. The
   real fix needs a `z_version` schema migration.
1. `ActionDiscovery` is flat per root: nested module controllers route on the web but do not
   appear in CLI `run` listings.
1. `MaintenanceHandler` and the email-layout probe in `Response.php` still probe userspace only.
1. No module uninstall (merged ini keys and executed migrations stay behind) and no per-module
   enable/disable beyond the ordering key.
1. `Engine::$prioritizeFrameworkViews` remains as an `@internal` escape hatch; its removal is
   planned, the Registry is the modular view resolver its docblock anticipated.

## Testing strategy

The two sample modules under `tests/e2e/modules/` are the executable specification, and they are
**permanently installed** in the e2e application: every future framework change is automatically
exercised against the module system, and the full suite must stay green with them installed.
`guestbook` is a complete feature module (migration, seed, model, nested controller and model,
routes, views, asset, namespaced service, ini defaults); `theme` is views and settings only and
exists to shadow the guestbook and the framework.

The spec group `tests/e2e/tests/cypress/e2e/modules/` proves:

1. `guestbook.cy.js`: a module works end to end, route and convention URL, POST round trip through
   the module's own table, css served through the asset proxy and actually linked.
1. `resolution.cy.js`: recursive find (nested controller and model by bare name), explicit dotted
   model paths (same file as the bare name, never rescued by the index), userspace shadows module,
   module shadows framework for views and proxy assets, app code consumes the module's PSR-4
   namespace.
1. `commands.cy.js`: module and userspace convention commands run, appear in `list`, and a
   userspace command wins a name collision with a module command.
1. `ordering.cy.js`: Composer installed order by default; listing the theme first in the `modules`
   key flips which module's view wins.
1. `setup-command.cy.js`: `module:setup` merge, first module winning shared keys, idempotent
   rerun, config snapshot/restore around the whole spec.

## module:setup

`src/Registry/Commands/ModuleSetup.php` is deliberately a proof of concept: it merges **missing**
keys from each module's `z_config/z_settings.ini` into the app ini, append-only, one commented
block per module, first module defining a key wins, manual CLI only, never at boot. It diffs
against the ini **file**, not the runtime store, so env-injected keys cannot suppress a merge.
Safety rules, because the writer is new while the ini reader is battle-tested: keys must match
`/^[A-Za-z0-9_.]+$/`, values containing line breaks or quotes are rejected (an injected line could
otherwise enable settings like `allow_env_config`), and the Configuration loader's placeholder
literals are rejected to keep the comment-escaping round trip intact. Failures name the module and
key and abort. The documented seam for richer module setup hooks (for example a class named in the
module's `composer.json` extra) sits directly after the merge loop.
