# Building a Module

This guide walks you through building a complete module from scratch: a guestbook that any ZubZet
application can install with one `composer require`. The module ships its own database table, model,
controller, routes, views, a stylesheet, a reusable service class, and default settings. At the end
you will re-skin it with a second module and learn how to test and publish your work.

The code in this guide is the framework's own sample module: every file you create here exists in
[`tests/e2e/modules/guestbook`](https://github.com/zubzet/framework/tree/develop/tests/e2e/modules/guestbook)
and is exercised by the framework's test suite on every change.

If you have not read the [Modules](../advanced-features/modules.md) page yet, skim it first: it
explains the resolution precedence (userspace, then modules, then framework) that this guide relies
on throughout.

### Resources
<details>
<summary>Database</summary>
```sql
CREATE TABLE `guestbook_entries` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `author` VARCHAR(64) NOT NULL,
    `message` VARCHAR(255) NOT NULL
);

INSERT INTO `guestbook_entries`(`author`, `message`) VALUES
('Migration', 'Guestbook is ready');
```

```sql
INSERT INTO `guestbook_entries`(`author`, `message`) VALUES
('Seed', 'Welcome to the guestbook'),
('Seed', 'Second seeded entry');
```
</details>

## Creating the Package

A module is a regular Composer package whose layout mirrors an application. Create a new directory
next to your application, for example `modules/guestbook`, and give it a `composer.json`:

```json
{
    "name": "zubzet/example-guestbook",
    "type": "zubzet-module",
    "version": "0.1.0",

    "autoload": {
        "psr-4": {
            "Module\\Guestbook\\": "app/"
        }
    }
}
```

Three things matter here:

1. The `type` must be `zubzet-module`. This is how the framework discovers the package; there is no
   registration file and no service provider.
1. The PSR-4 namespace roots at `app/`, exactly like the `App\` namespace of an application
   skeleton. A module is built the same way an application is.
1. The explicit `version` is only needed while the module lives in a directory without its own git
   tags (as during local development). Once you publish the module and tag releases, remove it and
   let Composer derive versions from the tags.

### Installing it into your application

During development, point a path repository at the module's directory from your application's
`composer.json`:

```json
{
    "repositories": [{
        "type": "path",
        "url": "modules/guestbook",
        "options": {
            "symlink": true
        }
    }],
    "require": {
        "zubzet/example-guestbook": "^0.1"
    }
}
```

Run `composer update zubzet/example-guestbook`. Because of the symlink option, every edit you make
in the module directory is live in the application immediately. This is the entire wiring: the
framework picks up any installed package of type `zubzet-module` on its own.

## Shipping the Database Schema

??? info "What is Migration and what is Seed?"
    **Migration** refers to the process of modifying a database's structure, such as adding,
    removing, or altering tables and columns. Migration files document these changes and allow them
    to be applied automatically across different environments.

    **Seeding** is the process of populating a database with sample or test data, commonly used in
    development and testing environments.

    More information can be found [here](../core-features/migrations/index.md)

The module owns its data, so it ships the schema. Create
`app/Database/migrations/2026-07-01_ExampleGuestbook.sql`:

```sql
CREATE TABLE `guestbook_entries` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `author` VARCHAR(64) NOT NULL,
    `message` VARCHAR(255) NOT NULL
);

INSERT INTO `guestbook_entries`(`author`, `message`) VALUES
('Migration', 'Guestbook is ready');
```

Migration state is tracked by the file's basename, and basenames must be unique across the
application, every module, and the framework. Always include your module's name in the filename so
it can never collide with a file from the host application or another module.

Development data goes into a seed file, `app/Database/seed/ExampleGuestbook.sql`:

```sql
INSERT INTO `guestbook_entries`(`author`, `message`) VALUES
('Seed', 'Welcome to the guestbook'),
('Seed', 'Second seeded entry');
```

Module migrations run as part of the normal `db:migrate` and are treated as **external**
migrations (like the framework's own), and `db:seed` runs the module's seeds after the
application's. Nothing to configure; run the commands in the host application:

```bash
php index.php db:migrate
php index.php db:seed
```

## The Model

??? info "What is a Model"
    A **Model** represents the data structure and business logic for data processing. It is
    responsible for interacting with the database to retrieve, store, update, or delete data.

    More information can be found [here](../core-features/models.md)

Module models are ordinary global model classes, identical to the ones you write in an
application. Create `app/Models/GuestbookModel.php`:

```php
<?php

    class GuestbookModel extends z_model {

        public function getEntries() {
            $query = $this->dbSelect(["id", "author", "message"], "guestbook_entries")
                            ->orderAsc("id");

            return $this->exec($query)->resultToArray();
        }

        public function addEntry($author, $message) {
            $query = $this->dbInsert("guestbook_entries", [
                "author" => $author,
                "message" => $message,
            ]);

            $this->exec($query);
        }
    }

?>
```

The [QueryBuilder](../core-features/query-builder/index.md) methods `dbSelect` and `dbInsert` work
exactly as they do in userspace models. Once the module is installed, any controller (in the
module, or in the host application) can retrieve this model with `$req->getModel("Guestbook")`.

Because module models are global classes resolved first-match, prefix your class names with the
module's name (`GuestbookModel`, not `EntryModel`) so they cannot collide with the host
application's own models.

## The Controller

??? info "What is a Controller and what is an Action?"
    A **Controller** manages the flow of data between the model and the view. An **Action** is a
    method within a controller that responds to a particular request.

    More information can be found [here](../core-features/controllers-and-actions.md)

Create `app/Controllers/GuestbookController.php` with one action to list entries and one to add
them:

```php
<?php

    class GuestbookController extends z_controller {

        public function action_index(Request $req, Response $res) {
            return $res->render("guestbook/index", [
                "entries" => $req->getModel("Guestbook")->getEntries(),
                "title" => config("guestbook_title", default: "Guestbook"),
            ]);
        }

        public function action_add(Request $req, Response $res) {
            $author = $req->getPost("author");
            $message = $req->getPost("message");

            $hasInput = is_string($author) && "" !== $author
                && is_string($message) && "" !== $message;

            if($hasInput) {
                // Clamp to the column widths so oversize input cannot error.
                $author = mb_substr($author, 0, 64);
                $message = mb_substr($message, 0, 255);
                $req->getModel("Guestbook")->addEntry($author, $message);
            }

            return $res->render("guestbook/index", [
                "entries" => $req->getModel("Guestbook")->getEntries(),
                "title" => config("guestbook_title", default: "Guestbook"),
            ]);
        }

    }

?>
```

Note the `config("guestbook_title", default: "Guestbook")` call: the page title comes from a
setting the module will provide a default for later, with a code fallback in case the host never
runs `module:setup`.

Module controllers are reachable by convention immediately: `/Guestbook/index` works as soon as
the file exists, just like a userspace controller.

## Routes

Convention URLs are fine, but a guestbook wants pretty URLs. Module route files live at the top
level of `app/Routes/` and use the same [routing API](../core-features/routing.md) as the
application. Create `app/Routes/GuestbookRoutes.php`:

```php
<?php
    use ZubZet\Framework\Routing\Route;

    Route::group('/guestbook', function() {
        Route::get('', [GuestbookController::class, 'action_index']);
        Route::post('/add', [GuestbookController::class, 'action_add']);
    });
?>
```

Module routes are loaded after the application's route files and before the framework's. Route
patterns must be unique across all three: a duplicate pattern is a hard error at request time, so
a module can never silently take over an existing URL. Pick a URL prefix that clearly belongs to
your module (here `/guestbook`), and never use `/_zubzet`, which is reserved for the framework.

## The Views

??? info "What is a View"
    A **View** is responsible for presenting data to the user. It defines the structure and layout
    of the user interface, rendering dynamic content based on data provided by the controller.

    More information can be found [here](../core-features/views.md)

Module views resolve through the normal Blade name lookup, between userspace views (which can
shadow them) and framework views (which they can shadow). Create
`app/Views/guestbook/index.blade.php`:

```blade
@extends($layout)

@section("content")
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/guestbook.css"); ?>">

    <h1 data-test="guestbook-title">{{ $opt["title"] }}</h1>
    <span data-test="guestbook-skin">default</span>

    @foreach($opt["entries"] as $entry)
        <div data-test="guestbook-entry">
            <span data-test="guestbook-entry-author">{{ $entry["author"] }}</span>:
            <span data-test="guestbook-entry-message">{{ $entry["message"] }}</span>
        </div>
    @endforeach

    <form method="POST" action="/guestbook/add">
        <input type="text" name="author" placeholder="Author" maxlength="64">
        <input type="text" name="message" placeholder="Message" maxlength="255">
        <button type="submit" data-test="guestbook-submit">Sign</button>
    </form>

    @include("guestbook.footer")
@endsection
```

Two lines deserve a closer look.

### Linking a module asset

The stylesheet link points at the [asset proxy](../core-features/asset-proxy.md). Everything in
the module's `webroot/` directory is served under `/_zubzet/asset-proxy/`, so create
`webroot/guestbook.css`:

```css
/* guestbook-css-marker */
[data-test="guestbook-entry"] {
    padding: 0.5rem 0.75rem;
    border-left: 3px solid #4a76a8;
    margin-bottom: 0.5rem;
}
```

Give asset files a name that includes your module's name unless shadowing is the point: module
sources mount before the framework's, so a module can deliberately replace a framework asset, and
earlier modules win over later ones. The application's own `webroot/` beats everything because the
web server serves it before PHP runs.

### Including a sub-view

`@include("guestbook.footer")` pulls in a second view. Create
`app/Views/guestbook/footer.blade.php`:

```blade
<footer data-test="guestbook-footer">module-footer</footer>
```

`@include`, `@extends`, and components resolve across all roots. That cuts both ways: the host
application can ship its own `app/Views/guestbook/footer.blade.php` and its copy wins, replacing
just the footer while the rest of the module's page stays intact. Sub-views are your module's
natural customization points.

Visit `/guestbook` in the host application: the seeded entries render, the form posts, and the
stylesheet loads.

## Organizing Code in Subdirectories

Modules are not limited to flat `Controllers/` and `Models/` directories. Bare names resolve
recursively (each root is probed flat first, then its subdirectories, shallowest match first), so
you can group related code. Create `app/Controllers/admin/GuestbookAdminController.php`:

```php
<?php

    class GuestbookAdminController extends z_controller {

        public function action_stats(Request $req, Response $res) {
            // Bare name "GuestbookStats" resolves the model one directory deep
            // inside the module root (recursive lookup).
            return $res->render("guestbook/admin", [
                "count" => $req->getModel("GuestbookStats")->countEntries(),
            ]);
        }

    }

?>
```

And the model it uses, `app/Models/reports/GuestbookStatsModel.php`:

```php
<?php

    class GuestbookStatsModel extends z_model {

        public function countEntries() {
            $query = $this->dbSelect("COUNT(*) AS entryCount", "guestbook_entries");

            return $this->exec($query)->resultToLine()["entryCount"];
        }
    }

?>
```

The admin view, `app/Views/guestbook/admin.blade.php`:

```blade
@extends($layout)

@section("content")
    <span data-test="guestbook-count">{{ $opt["count"] }}</span>
@endsection
```

Despite living in subdirectories, the controller answers at `/GuestbookAdmin/stats` and the model
is retrieved as `getModel("GuestbookStats")`: the directory layout is an organizational choice,
not part of the name.

## A Namespaced Service Class

Everything so far used global classes, because that is what the framework's conventions load. For
code the host application should call directly, use the module's PSR-4 namespace. Create
`app/Support/EntryFormatter.php`:

```php
<?php

    namespace Module\Guestbook\Support;

    final class EntryFormatter {

        public static function format(string $author, string $message): string {
            return "{$author}: {$message}";
        }
    }

?>
```

The namespace follows from the PSR-4 root in `composer.json`: `Module\Guestbook\` maps to `app/`,
so a class in `app/Support/` lives in `Module\Guestbook\Support`. Composer autoloads it the moment
the package is installed, and the host application can use it like any dependency:

```php
<?php

    use Module\Guestbook\Support\EntryFormatter;

    class ModuleHostController extends z_controller {

        public function action_service(Request $req, Response $res) {
            echo EntryFormatter::format("QA", "module namespace works");
        }

    }

?>
```

## A Console Command

A module can ship console commands in `app/Commands/`. One global class per file, named like the
file, extending the Symfony `Command` class. Create `app/Commands/GuestbookStatsCommand.php`:

```php
<?php

    use Symfony\Component\Console\Command\Command;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Output\OutputInterface;

    class GuestbookStatsCommand extends Command {

        protected function configure(): void {
            $this->setName("guestbook:stats");
            $this->setDescription("Print the number of guestbook entries.");
        }

        protected function execute(InputInterface $in, OutputInterface $out): int {
            $count = model("GuestbookStats")->countEntries();
            $out->writeln("guestbook-entries:$count");
            return Command::SUCCESS;
        }
    }

?>
```

Run it with `php index.php guestbook:stats`; it also shows up in `php index.php list`. The
framework is fully booted before `execute()` runs, so `model()` and `config()` work as usual. If
the application defines a command with the same name, the application's copy wins, which gives
consumers a clean way to replace a module command.

## Default Settings and module:setup

The controller reads `config("guestbook_title")`. Ship a default for it (and any other setting) in
`z_config/z_settings.ini` at the module root:

```ini
guestbook_title = Signatures Book
guestbook_page_size = 25
```

Prefix setting keys with your module's name; the settings file is one flat namespace shared by the
application and every module.

The host application imports these defaults by running:

```bash
php index.php module:setup
```

The command appends every key the application's `z_config/z_settings.ini` does not define yet, as
a commented block:

```ini
; Defaults added by module:setup from zubzet/example-guestbook
guestbook_title = Signatures Book
guestbook_page_size = 25
```

The merge is append-only and idempotent: existing keys are never touched, and a rerun reports
`nothing to merge`. After the merge, `/guestbook` shows "Signatures Book" instead of the code
fallback, and the host can edit the value like any other setting.

## Re-skinning with a Second Module

Modules can build on each other. To prove it, create a second, much smaller module that re-skins
the guestbook without touching its logic. It needs only two directories:

```
theme/
├── composer.json
├── app/
│   └── Views/
│       └── guestbook/
│           └── index.blade.php
└── z_config/
    └── z_settings.ini
```

`composer.json`:

```json
{
    "name": "zubzet/example-theme",
    "type": "zubzet-module",
    "version": "0.1.0",

    "autoload": {
        "psr-4": {
            "Module\\Theme\\": "app/"
        }
    }
}
```

Its `app/Views/guestbook/index.blade.php` is a copy of the guestbook's view with a different look
(the sample adds a banner and changes the skin marker):

```blade
@extends($layout)

@section("content")
    <link rel="stylesheet" href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/guestbook.css"); ?>">

    <div data-test="theme-banner">Themed by example-theme</div>
    <h1 data-test="guestbook-title">{{ $opt["title"] }}</h1>
    <span data-test="guestbook-skin">theme</span>

    @foreach($opt["entries"] as $entry)
        <div data-test="guestbook-entry">
            <span data-test="guestbook-entry-author">{{ $entry["author"] }}</span>:
            <span data-test="guestbook-entry-message">{{ $entry["message"] }}</span>
        </div>
    @endforeach

    <form method="POST" action="/guestbook/add">
        <input type="text" name="author" placeholder="Author" maxlength="64">
        <input type="text" name="message" placeholder="Message" maxlength="255">
        <button type="submit" data-test="guestbook-submit">Sign</button>
    </form>

    @include("guestbook.footer")
@endsection
```

The theme keeps rendering with the guestbook's controller, model, and routes; it only replaces the
view. It can also ship its own overlapping defaults in `z_config/z_settings.ini`:

```ini
guestbook_title = Themed Signatures Book
theme_accent = coral
```

### Controlling module order

Install the theme through a second path repository, then visit `/guestbook`: you still see the
original skin. Both modules provide `guestbook/index`, and without further configuration modules
resolve in Composer installed order, where the guestbook comes first.

The `modules` key in the application's `z_config/z_settings.ini` decides:

```ini
modules = zubzet/example-theme, zubzet/example-guestbook
```

Listed modules come first, in the listed order; now the theme's view wins and `/guestbook` renders
the themed page with the same entries and the same form. Remove the key (or reorder it) to switch
back. The same ordering applies to `module:setup`: for shared keys like `guestbook_title`, the
first module in module order provides the merged default.

A view-only module can shadow framework views the same way. The sample theme also ships
`app/Views/email_too_many_logins.blade.php`, replacing the framework's security notification email
for the whole application.

## Testing Your Module

The path repository setup from the beginning of this guide is also the testing setup: a module is
always tested from inside a host application, because only there do routes, migrations, views, and
settings come together. Keep a small host application next to your module, symlink the module in,
and run your usual tests against the host.

This is exactly how the framework tests its own module system: the guestbook and theme modules you
just built are permanently installed in the framework's e2e test application via path
repositories, and the whole test suite (including every non-module test) runs with them present.
If your module needs schema, remember that `db:migrate` and `db:seed` in the host cover the
module's files too, so a test run can start from a fresh database.

## Publishing

A module is published like any other Composer package: push it to a git repository, tag a release,
and either submit it to [Packagist](https://packagist.org) or serve it from your own Composer
repository. Consumers then install it with:

```bash
composer require zubzet/example-guestbook
```

Two housekeeping steps before the first tag:

1. Remove the `version` field from `composer.json`; published packages get their version from git
   tags.
1. Add a `.gitattributes` file with `export-ignore` entries for anything consumers do not need
   (your test host application, CI configuration, development tooling), so the installed package
   stays lean:

```text
/tests export-ignore
/.github export-ignore
```

That is the entire lifecycle: a directory that mirrors an application, one `type` field, and
Composer does the rest. For the reference of all module semantics, see
[Modules](../advanced-features/modules.md).
