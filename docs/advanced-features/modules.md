# Modules

Since version **1.4.0**, ZubZet applications can be extended with **modules**: Composer packages of
type `zubzet-module` whose layout mirrors an application. A module can contribute controllers,
models, views, routes, migrations, seeds, and static assets. The framework resolves everything it
loads by convention through a single precedence rule, so a module plugs in without any registration
code.

## Module layout

A module is a regular Composer package. Its directory structure mirrors the userspace skeleton:

```
my-module/
├── composer.json                 type "zubzet-module"
├── app/
│   ├── Controllers/              global controller classes, subdirectories allowed
│   ├── Models/                   global model classes, subdirectories allowed
│   ├── Views/                    Blade views
│   ├── Routes/                   route files, top level only
│   ├── Commands/                 Symfony console commands, subdirectories allowed
│   ├── Database/
│   │   ├── migrations/
│   │   └── seed/
│   └── Support/                  the module's own namespaced code
├── webroot/                      static assets, served via the asset proxy
└── z_config/
    └── z_settings.ini            default settings for module:setup
```

`composer.json` declares the package type and a PSR-4 namespace rooted at `app/`:

```json
{
    "name": "acme/blog-module",
    "type": "zubzet-module",
    "autoload": {
        "psr-4": {
            "Acme\\Blog\\": "app/"
        }
    }
}
```

Every directory is optional; the framework only reads the ones that exist. The PSR-4 root
overlapping the convention directories is harmless: controllers and models declare global classes
and are included by file path, never requested through the autoloader.

## Installing a module

A module is installed like any other dependency:

```bash
composer require acme/blog-module
```

While developing a module locally, point a path repository at its checkout:

```json
{
    "repositories": [{
        "type": "path",
        "url": "../blog-module",
        "options": { "symlink": true }
    }],
    "require": {
        "acme/blog-module": "*@dev"
    }
}
```

No further wiring is needed: the framework discovers every installed package of type
`zubzet-module` automatically.

## Resolution precedence

All convention lookups resolve in the same order:

1. **Userspace** (the application itself)
1. **Modules**, in module order
1. **Framework** (`IncludedComponents`)

The first root that contains a matching file wins. An application therefore shadows any module, and
a module shadows the framework. This applies to views, controllers, models, routes, and seeds;
migrations and assets have their own semantics described below.

Module order is controlled by the optional `modules` key in `z_config/z_settings.ini`, a
comma-separated list of package names:

```ini
modules = "acme/blog-module, acme/shop-module"
```

Listed modules come first, in the listed order. Installed modules that are not listed follow in
Composer installed order, so the key is only needed when the order matters. Names that are listed
but not installed are ignored.

A module can also be switched off without uninstalling it (useful when a dependency pulls a
module in transitively) via the `modules_disabled` key:

```ini
modules_disabled = "acme/unwanted-module"
```

Disabled modules are excluded from every lookup, from `module:setup`, and from the `info:startup`
module list.

## What a module contributes

| Kind | Module path | Semantics |
| ---- | ----------- | --------- |
| Views | `app/Views/` | Resolve between userspace and framework views |
| Controllers | `app/Controllers/` | Reachable by convention, e.g. `/BlogPost/index` |
| Models | `app/Models/` | Available via `$req->getModel()` / `model()` |
| Routes | `app/Routes/` | Loaded after userspace route files, before framework routes |
| Commands | `app/Commands/` | Registered in the console next to framework commands |
| Migrations | `app/Database/migrations/` | Join the external migration set |
| Seeds | `app/Database/seed/` | Run by `db:seed` after userspace seeds |
| Assets | `webroot/` | Served via `/_zubzet/asset-proxy/` |

### Views

Module views take part in the normal Blade name resolution: an application view shadows a
same-named module view, and a module view shadows a same-named framework view. `@extends`,
`@include`, and components resolve across all roots.

### Controllers and models

Module controllers and models are plain global classes, exactly like userspace ones
(`class BlogPostController extends z_controller`). Resolution is first match wins for the whole
request: an application can override a module controller wholesale by shipping a same-named file.
To avoid accidental collisions, prefix your class names with the module name unless shadowing is
the intended extension point.

### Routes

Route files at the top level of `app/Routes/` (subdirectories are not loaded) are required after
the application's route files and before the framework's. Byte-identical route patterns are a hard
error: FastRoute throws instead of silently preferring one registration. Overlapping variable
patterns are not detected, though; they coexist, and the first registration wins. Convention URLs
(`/Controller/action`) are not FastRoute registrations at all, so an explicit module route matching
such a URL takes it over silently. Prefix your module's routes with a name that clearly belongs to
it. The `/_zubzet` prefix is reserved for the framework.

### Commands

Files in `app/Commands/` declare one global class each, named like the file and extending the
Symfony `Command` class. They are discovered from the application first, then from every module in
module order, and registered next to the framework's own commands:

```php
<?php
    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class BlogPruneCommand extends Command {
        protected function configure(): void {
            $this->setName("blog:prune");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            model("BlogPost")->pruneDrafts();
            return Command::SUCCESS;
        }
    }
?>
```

Run it with `php index.php blog:prune`. When two roots register the same command name, the usual
precedence applies: the application wins over modules, modules win over the framework.

### Migrations

Module migrations count as **external** migrations, like the framework's own:

- `db:migrate --exclude-external` skips them.
- They are exempt from the skipped-timeline check unless `--enforce-external-timeline` is set,
  because a freshly installed module always carries files dated in the past.
- Executed state is keyed on the file's basename. Duplicate basenames across any two roots abort
  the run with an error, so prefix your migration filenames with the module name, for example
  `2026-07-28_acme-blog_CreatePosts.sql`.

### Seeds

`db:seed` runs userspace seeds first, then each module's seeds in module order. The `-e`/`-i`
environment selectors are root-relative, so `-e Environments/Prod` applies inside the application
and inside every module.

### Assets

A module's `webroot/` directory is registered as an asset-proxy source, so its files are served at:

```
/_zubzet/asset-proxy/<path>
```

Module sources mount before the framework's own sources, following the global precedence: a module
can shadow a framework, frontend, or bundled asset, and earlier modules win over later ones. The
application's `webroot/` sits above everything because the web server serves it before PHP runs.
Files with a `.php`, `.phtml`, or `.ini` extension are never served from any source. See
[Asset Proxy](../core-features/asset-proxy.md).

## Recursive lookup

Bare controller and model names resolve into subdirectories: each root is probed flat first, and
only after a flat miss is the root's directory tree searched, shallowest match first. A module can
therefore organize its code in subdirectories (`app/Controllers/admin/StatsController.php`) and the
controller stays reachable by its bare name (`/Stats/...`). The same applies to userspace and
framework roots.

## module:setup

Modules can ship default settings in `z_config/z_settings.ini`. Running

```bash
php index.php module:setup
```

appends every key the application's `z_config/z_settings.ini` does not define yet, one commented
block per module:

```ini
; Defaults added by module:setup from acme/blog-module
blog_posts_per_page = 10
```

The merge is append-only and idempotent: existing keys are never overwritten or reordered, a rerun
reports `nothing to merge`, and the first module defining a key wins. The command is manual and
never runs at boot.

## Using module code from the application

The module's PSR-4 namespace is autoloaded by Composer the moment the package is installed, so its
classes are directly usable from application code:

```php
use Acme\Blog\Support\Excerpt;

class HomeController extends z_controller {
    public function action_index(Request $req, Response $res) {
        echo Excerpt::of($req->getModel("BlogPost")->latest());
    }
}
```

## Trust model

Installing a module means executing its code: route files are loaded on every request, controllers
and models run inside your application, and PHP migrations execute during `db:migrate`. The
security boundary is `composer install`. Only install modules you trust, exactly as with any other
Composer dependency.

## Writing a module

The canonical examples are the two sample modules used by the framework's own test suite:

- [`tests/e2e/modules/guestbook`](https://github.com/zubzet/framework/tree/develop/tests/e2e/modules/guestbook)
  is a complete feature module: it ships a migration and seed for its own table, a model, a
  controller, routes, views, a stylesheet served through the asset proxy, a namespaced service
  class, and default settings.
- [`tests/e2e/modules/theme`](https://github.com/zubzet/framework/tree/develop/tests/e2e/modules/theme)
  re-skins the guestbook: it contains only views and settings. It demonstrates that modules can
  shadow other modules' views (order decides) and framework views, and that a module needs only
  the directories it actually uses.

To build one:

1. Start from `composer.json`: pick a package name, set the type to `zubzet-module`, and root a
   PSR-4 namespace at `app/`.
1. Add convention files as needed: a controller under `app/Controllers/`, a model under
   `app/Models/`, views under `app/Views/`, a route file under `app/Routes/`.
1. Ship schema and data as `app/Database/migrations/` and `app/Database/seed/` files with
   module-prefixed basenames.
1. Put static files in `webroot/` and reference them via `/_zubzet/asset-proxy/`.
1. Declare default settings in `z_config/z_settings.ini` so consumers can run `module:setup`.
1. During development, require the module through a path repository (see above); publish it like
   any Composer package when it is ready.

For a complete walkthrough that builds the guestbook module file by file, follow the
[Building a Module](../guides/building-a-module.md) guide.

## What module authors need to know

The sections above describe each kind in isolation. These are the architecture facts worth
internalizing before you design a module:

- **Controllers and models are global classes, loaded first-match.** There is no per-module class
  namespace for convention files: the first root that has the file wins for the whole request.
  Prefix your names with the module name (`GuestbookModel`, not `EntryModel`) unless shadowing is
  the point. The flip side is a real feature: an application can deliberately override any module
  file (a controller, a model, a single sub-view) by shipping the same path itself.
- **One name resolves to one file per request.** Lookups are memoized, so a name can never resolve
  to different files within a single request, not even during error-page rendering. What you see
  on the first hit is what every later consumer gets.
- **Route patterns must be unique.** Byte-identical patterns across application, modules, and
  framework are a hard error. Merely overlapping patterns and convention URLs are not protected:
  the first registration wins, silently. Choose a URL prefix that clearly belongs to your module;
  `/_zubzet` is reserved for the framework.
- **Migration basenames must be unique across all roots.** Executed state is keyed on the
  basename, and a duplicate aborts the run. Put the module name in every migration filename.
  Module migrations count as external: consumers can exclude them with
  `db:migrate --exclude-external`, and they are exempt from the skipped-timeline check by default.
- **Recursive lookup finds bare names in subdirectories, shallowest first.** You can organize
  controllers and models in subdirectories without changing how they are addressed; the directory
  layout is organizational, not part of the name.
- **Assets follow the same precedence as everything else.** Module `webroot/` sources mount before
  the framework's, so a module can deliberately replace a framework asset, and the application's
  own `webroot/` beats everything (the web server serves it before PHP runs). Name asset files
  after your module unless shadowing is the point.
- **Commands resolve by Symfony name, userspace last-word.** A command in `app/Commands/` is a
  global class named like its file, extending the Symfony `Command` class. When two roots register
  the same command name, the userspace copy wins, then modules in order, then the framework; an
  application can deliberately override a module or framework command by reusing its name.
- **Some surfaces are not module-aware yet.** The console `run` command's controller listing is
  flat: it sees only the top level of the userspace and module controller directories. The
  maintenance page and the email layout probe look only at userspace. These are documented
  follow-ups, not extension points.
- **Convention commands boot the framework.** Command classes run after the full bootstrap, so
  `config()`, `model()`, and the database are available inside `execute()`.
- **The debug bar shows every resolution.** In dev environments the
  [Resolutions tab](../core-features/debug-bar.md#resolutions) lists which root won each
  controller, model, and route file on the page, so shadowing is always inspectable.

## Overriding module behavior today

Two supported recipes cover most "I want to change what this module does at this URL" cases
without waiting for a hook system:

1. **Claim a convention URL with your own route.** Module controllers reached by convention
   (`/Guestbook/index`) are not FastRoute registrations, and application route files load first,
   so a route in your `app/Routes/` simply takes the URL over:

    ```php
    use ZubZet\Framework\Routing\Route;

    Route::get('/Guestbook/index', [MyGuestbookController::class, 'action_index']);
    ```

    Note that re-registering a route pattern a module already registers byte-identically is a hard
    error, not an override; this recipe applies to convention URLs and new patterns.

1. **Shadow the controller file.** Route handlers and convention dispatch both resolve controller
   classes through the Registry, so shipping a same-named controller in your application replaces
   the module's implementation everywhere it is referenced, including inside the module's own
   registered routes. This is file-level and coarse: your copy takes over every action of that
   controller.

A finer-grained mechanism (an event and hook system) is on the roadmap; see the pull request that
introduced the module system for context.

For the internals behind these rules (the Registry, its kind table, and the lookup fast and slow
paths), see the maintainer documentation:
[Module System Architecture](../contributing/module-system-architecture.md).
