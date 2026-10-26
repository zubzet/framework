### Console Commands

ZubZet provides a built-in console interface that allows you to manage and interact with the framework directly from the command line.
Console commands enable automation, debugging, and execution of application logic outside the HTTP context.

### How to Run a Command

To execute a console command inside the [Docker container](../setup/installation.md), first access the container shell:

```bash
npm run shell
```

Then navigate to the root directory of your project (where the `index.php` file is located) and run:

```bash
php index.php {command}
```

The package registers no Composer `scripts` and no `bin` executable (its only `require-dev` entry is a test-coverage library), so `php index.php <command>` is the only way to reach the CLI. A command's return value becomes the process exit status (`0` on success, `1` on failure).

### Available Commands

### completion

Dumps the shell completion script for the ZubZet console.
This improves command-line usability by enabling tab completion.

### help

Displays help information for a specific command or for the console in general.
Symfony's global options (`-h`, `-q`, `-v|-vv|-vvv`, `-V`, `--ansi`, `--no-ansi`, `-n`) apply to every command.

### list

Lists all available console commands; `php index.php` without a command prints the same list.

### run

Executes a [controller action](controllers-and-actions.md) directly from the console environment.
This is useful for [running application logic](../advanced-features/commands.md), maintenance tasks, or background operations without an HTTP request.

### info:startup

Prints a startup banner with the ZubZet version (as Composer reports it, e.g. `v1.2.0`, or `unknown`), application name, environment, PHP version, and asset
version — a quick health check after deployment. A missing `pageName`, `execution_type`, `assetVersion` or `host` prints `Unknown. Set <key> in settings!` (`host` prints `Unknown / Check docker-compose-base.yml`) instead.

```bash
php index.php info:startup
```

Pass `--pwd "$(pwd)"` so clickable file links in the [error page](error-handling.md) resolve to your
host paths (the dev stack's `npm run info` does this for you). The value is stored in
`z_config/z_automated_setting.ini` as `automated_host_working_directory`.

### install

Builds a new ZubZet project in an existing, writable folder.

```bash
php index.php install /path/to/new-project
```

The command copies the project template into the folder and prints a `created:` or `exists:` line
per entry. Existing files are never overwritten, so it is safe to run on a folder that already
contains files. It then makes `zubzet` and `project` executable and runs `composer require` for
the framework inside the new project.

It fails with a clear message if the folder does not exist, is not writable, or Composer is not
in your `PATH`.

### project

Every generated project ships a `project` script in its root for the everyday tasks:

| Command | Description |
| ------- | ----------- |
| `./project start` | Build and start the Docker stack, then run `composer install`, `db:seed` and `info:startup`. |
| `./project stop` | Stop the stack and remove it, including its volumes. |
| `./project shell` | Open a shell inside the application container. |
| `./project tests` | Run the Cypress suite headless; extra arguments (e.g. `--spec`) are passed through. |
| `./project cypress` | Open the interactive Cypress UI. |

Any other arguments are forwarded to `php index.php`: inside the application container when it
is running, otherwise directly with the host's PHP.

```bash
./project db:status
```

`tests` and `cypress` need Node.js and Cypress on the host. They are never installed
automatically; if they are missing, the script prints the `npm install` command to run.

### Database commands

| Command | Description |
| ------- | ----------- |
| `db:migrate` | Run all pending migrations (framework-bundled and your own under `app/Database/migrations`). |
| `db:seed` | Run migrations, then load seed data from `app/Database/seed`. |
| `db:status` | Show whether the migration lock is set; exit code `0` when unlocked, `1` when locked. |
| `db:sync` | Mark migrations as applied up to a version/date without running their SQL. |
| `db:unlock-migration` | Release a stuck migration lock. |

`db:seed` accepts environment filters to control which seed folders run:

```bash
# Only seed the "Dev" environment
php index.php db:seed --environments-included=Dev

# Seed everything except "Prod" (repeatable)
php index.php db:seed --environments-excluded=Prod

# Skip the automatic migration step
php index.php db:seed --skip-migrations
```

See [Migrations](migrations/index.md) for the full migration workflow.

### Repair commands

Repair commands fix an installation that drifted into a broken state. They are never run
automatically; run one when you hit the problem it names.

#### repair:database-collation

Tables with different collations make queries across them fail, for example with
`Illegal mix of collations for operation 'UNION'` on every authenticated request. This happens
when tables were created under different server or database defaults, e.g. older tables from
before a server upgrade next to newer ones.

The framework creates its tables as `utf8mb4` with the collation `utf8mb4_uca1400_ai_ci`
(`ZubZet\Framework\Database\Connection::COLLATION`). The command converts the framework tables
(`z_` prefix) to it with
`ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci`:

```bash
php index.php repair:database-collation
```

Application tables and the database default are left alone. Add `--include-app-tables` to convert
every table of the database and to set the database default to the same collation, so tables
created later without an explicit collation match:

```bash
php index.php repair:database-collation --include-app-tables
```

Only tables whose own collation or any text column's collation differs are converted; a rerun
with nothing left to convert changes nothing. Columns that deliberately use another collation
(e.g. a case-sensitive `utf8mb4_bin` column) are converted as well.

!!! warning
    Converting rewrites the whole table and blocks writes to it meanwhile, which can take a
    long time on large tables. Take a backup and run it in a maintenance window.

### auth:migrate-hashing

Wraps every `legacy` password hash in an Argon2id layer and marks the row `onion`; rows without a password are skipped. Idempotent, so
it is safe to run more than once, but the update does not re-check the scheme, so a password changed during the run can be overwritten. See [Password Handling](password-handling.md#migrating-an-existing-installation).

```bash
php index.php auth:migrate-hashing
```

With `--dry` (`-d`) it only prints how many legacy passwords it found.

### module:setup

Merges missing default settings from installed [modules](../advanced-features/modules.md) into
`z_config/z_settings.ini`. Append-only and idempotent: existing keys are never touched, and a
rerun with nothing to merge changes nothing.

```bash
php index.php module:setup
```

### Your own commands

Beyond `run`, the application (and every module) can ship dedicated Symfony commands in
`app/Commands/`; they register automatically and appear in `list`. See
[Commands](../advanced-features/commands.md) for the file convention and precedence rules.

### Coverage

Coverage is a development feature: the underlying `phpunit/php-code-coverage` library is a
development dependency of ZubZet and is not installed with your application by default.
Install it before using the coverage commands:

```bash
composer require --dev "phpunit/php-code-coverage:9.*"
```

Without it, the coverage commands fail with an error explaining this requirement.

Collect a runtime code-coverage report:

```bash
php index.php testing:coverage:start
# ... exercise the app via tests or manual requests ...
php index.php testing:coverage:stop
```

`start` writes a session id to `.coverage.session` in the working directory; while that file exists, every web request and CLI call collects coverage (an Xdebug or PCOV driver is required). `testing:coverage:stop` writes the HTML report to `.coverage/report/`; add `--cli` (`-c`) to print a text report (PHPUnit summary plus a per-file tree, green from 90 %, yellow from 50 %, red below) to stdout instead. Merging many collected files is memory-hungry; CI runs it as `php -d memory_limit=4G index.php testing:coverage:stop`.