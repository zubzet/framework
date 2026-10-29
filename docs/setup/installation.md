# Installation
ZubZet projects are set up with an install script. It builds the project and bundles a Docker development stack: Apache + PHP, MariaDB, phpMyAdmin, and a mail catcher.

## Requirement
[**Docker**](https://docs.docker.com/get-started/get-docker/) is required. Nothing else needs to be installed on your host.

### Database server
ZubZet requires **MariaDB 11.7 or newer** and does not support MySQL: the bundled migrations use MariaDB-only syntax (`ADD COLUMN IF NOT EXISTS`, `ADD INDEX IF NOT EXISTS`, `DROP COLUMN IF EXISTS`) and the native `UUID` column type. The Docker stack of a new project runs `mariadb:11.8`, and the framework's e2e suite runs MariaDB 11.8 as a three-node Galera cluster.

No other SQL dialect is wired up: the runtime connection is `mysqli`, the migration tooling is hard-coded to the DBAL `mysqli` driver with `MySQL80Platform`, and the query builder uses the `Cake\Database\Driver\Mysql` driver.

### PHP extensions
The Docker image provides every extension the framework needs. On your own PHP build, `composer.json` only requires `ext-openssl`, but you also need:

- `ext-mysqli` built against `mysqlnd`: every query calls `mysqli_stmt::get_result()`, which the `libmysqlclient` build of mysqli does not offer.
- `ext-mbstring` and `ext-fileinfo`: the framework calls `mb_*` functions (e.g. in `Helpers`), and the asset proxy detects MIME types through `league/mime-type-detection`, which needs fileinfo.
- `ext-ctype`, `ext-filter` and `ext-hash`: required by PHPMailer, which sends the mails of `Response::sendEmail()`.
- Argon2id support: new password hashes use `password_hash()` with `PASSWORD_ARGON2ID`, so PHP must be compiled with Argon2 (`--with-password-argon2`, or the `PASSWORD_ARGON2ID` constant is undefined).

## Install script
One command creates a project in a new folder (replace `my-project`):

```bash
bash <(curl -fsSL https://zubzet.com/install.sh) my-project
```

Without the folder argument the script asks for it. `https://zubzet.com/install.sh` redirects to the script inside this documentation for the latest stable release, `https://zubzet.com/docs/latest/setup/install.sh`. The script is versioned with the framework, so for another version fetch it from that version's docs, `https://zubzet.com/docs/<version>/setup/install.sh`. It is printed in full below so you can read what it does before running it, or save it as `install.sh` and run it with `bash install.sh my-project`.

```bash
--8<-- "docs/setup/install.sh"
```

## Install with an agent
If an AI coding agent (Claude Code, Cursor, Codex, ...) does the setup or the work on an existing project, give it this one line first:

```text
Fetch https://zubzet.com/agent.md and follow it before working on this ZubZet project. If you cannot fetch it, do nothing and tell me.
```

The page is a short signpost for agents. It tells them to run the install script above for a new project, where the documentation and the agent skill live inside `vendor/zubzet/framework/` (`docs/` and `skills/zubzet/`), how the `project` script drives the Docker stack, and that a project's own `AGENTS.md` wins over the skill. Two things to know before you paste it:

- **Docker must be installed and the agent must be allowed to run it.** The install script, the application and the tests all run in containers; an agent in a sandbox without Docker can read the sources but cannot install, start or test the project.
- **The agent reads the skill.** The skill is a directory in the framework package, `vendor/zubzet/framework/skills/zubzet`, that holds the architecture, design and style rules for ZubZet projects. Every agent can read it right there. A tool that loads project skills from a folder of its own gets a link to that directory, which the agent offers to create; the framework itself creates no tool specific folder in a project.

The same prompt works on an existing project at any time; it is the fastest way to give a new agent session the full context.

## Start the project
Inside your project folder, run:

```bash
./project start
```

This single command:

1. Builds the application image and starts the Docker stack (detached),
2. Runs `composer install` inside the application container,
3. Seeds the database with `db:seed`, which applies all pending migrations,
4. Prints the startup info with `info:startup`.

To stop and remove the stack (and its volumes), run `./project stop`. `./project shell` opens a shell inside the application container. See [console commands](../core-features/console-commands.md#project) for all `project` commands.

## Access your application
| Service | URL |
| ------- | --- |
| Application | <http://localhost:8080> |
| Database UI (phpMyAdmin) | <http://localhost:8081> |
| Mail catcher (smtp4dev) | <http://localhost:3300> |

The seed creates no user accounts. `app/Database/seed/accounts/0_permissions.sql` adds the roles `Admin` (permission `*.*`) and `User` (permission `dashboard`); create a user and assign it a role, see [Z-Admin permissions](../z-admin/usage.md#permissions). Adjust or remove the seed before going to production.

## Project layout
`tests/e2e/` in the framework repository is a complete reference project. The framework resolves every default path relative to the process working directory, so an entry script has to `chdir()` to the project root before booting (the stub `index.php` does `chdir(realpath(__DIR__))`, `webroot/index.php` changes to its parent folder). This matters for cron jobs and other CLI calls started from elsewhere.

- `index.php`: entry script in the project root that boots the framework, for HTTP requests (via `webroot/index.php`, which requires the first existing of `index.php`, `zubzet.php` and `zubzet`) and for CLI use (`php index.php <command>`). It calls `chdir(realpath(__DIR__))`, requires `autoload.php` from `$COMPOSER_VENDOR_DIR` (default `./vendor`), then runs `new z_framework()` (an alias of `ZubZet`) and `->execute()`. Web and CLI share this script: for `php index.php <command>`, `execute()` hands over to the console when no route matches.
- `app/Controllers/`: default `z_controllers` directory with the `<Name>Controller.php` files. A controller in a sub-folder is found by its plain name, see [controllers](../core-features/controllers-and-actions.md#controller-names-and-case).
- `app/Models/`: default `z_models` directory with the `<Name>Model.php` files. A sub-folder is addressed in lower case through dot notation, `getModel("reports.GuestbookStats")` loads `reports/GuestbookStatsModel.php`.
- `app/Views/`: default `z_views` directory with views, mail templates and the `layout/` folder. Further sub-folders are allowed and addressed in the view name (`employee/index`).
- `app/Routes/`: default `routes` directory. Every top-level `*.php` file in it is `require_once`d when the route dispatcher is built on the first request, sub-folders are not read.
- `app/Database/migrations/`: migration files read by `db:migrate` and `db:sync`. A missing folder is created with mode `0755`.
- `app/Database/seed/`: seed files read by `db:seed`. Sub-folders can be used as selectors for `-i` and `-e`. A missing folder is created with mode `0755`.
- `z_config/z_settings.ini`: settings file at the default `config_file` path. It is read without an existence check, so a project without it does not boot.

## Web root
Apache serves only `webroot/` (`DocumentRoot /var/www/html/webroot`, see `packaging/docker/site-000-default.conf`). Everything above it is outside the web root and cannot be requested, so `/app/Controllers/AdminController.php`, `/composer.json`, `/vendor/autoload.php` and `/z_config/z_settings.ini` answer 404 and never expose source code or settings. The `.htaccess` in `webroot/` (`RewriteCond %{REQUEST_FILENAME} !-f` with `RewriteRule . index.php [L,QSA]`) sends every request that is not an existing file to `webroot/index.php` and keeps the query string, so such a request reaches the framework router, which has no route for it, and the response is the framework's 404 page. Existing files in `webroot/` (assets, `uploads/`) are served by Apache directly, and PHP files there are executed directly, bypassing the framework. Keep secrets and application code out of `webroot/`.

The rewrite rule is an Apache `.htaccess`, so it needs `mod_rewrite` and `AllowOverride All` for the document root (set in `packaging/docker/site-000-default.conf` of the Docker stack). Any other web server needs an equivalent rule that sends requests for non-existing files to `webroot/index.php`.

## Configuration
Settings live in `z_config/z_settings.ini`. When `allow_env_config = true`, any setting can be overridden by a `CONFIG_*` environment variable. The Docker stack uses this to inject the database and mailer credentials (see `packaging/docker/docker-compose-base.yml`). Keep secrets out of the committed INI file and provide them via the environment, but declare a placeholder such as `dbpassword = env` in the INI for each of them: a variable is ignored unless its key already exists (see [environment variable overrides](../core-features/configuration.md#environment-variable-overrides)).

Three keys must exist in the INI, otherwise the framework does not boot: `host` (the base URL, e.g. `http://localhost:8080`), `rootDirectory` (the sub-path the app is served from; the key must be present, but its value may be empty; write it without leading or trailing slash, although surrounding slashes and backslashes are trimmed before use) and `showErrors` (`0`, `1` or `2`, see [error handling](../core-features/error-handling.md#showerrors-levels)). A missing key throws an `InvalidArgumentException` ("The attribute '...' does not exist in the attribute store (yet)") during startup. `CONFIG_*` environment variables and constructor parameters only override keys that already exist, so they cannot supply a missing one. The project stub in `stubs/project/z_config/z_settings.ini` already defines all three.

## Next steps
- [Migrations](../core-features/migrations/index.md) — schema management and the migration CLI.
- [Configuration](../core-features/configuration.md) — every available setting.
- [MVC](../core-features/mvc.md) and [Routing](../core-features/routing.md) — build your first pages.
