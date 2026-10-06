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

### Available Commands

### completion

Dumps the shell completion script for the ZubZet console.
This improves command-line usability by enabling tab completion.

### help

Displays help information for a specific command or for the console in general.

### list

Lists all available console commands.

### run

Executes a [controller action](controllers-and-actions.md) directly from the console environment.
This is useful for [running application logic](../advanced-features/commands.md), maintenance tasks, or background operations without an HTTP request.

### info:startup

Prints a startup banner with the ZubZet version, application name, environment, PHP version, and asset
version — a quick health check after deployment.

```bash
php index.php info:startup
```

Pass `--pwd "$(pwd)"` so clickable file links in the [error page](error-handling.md) resolve to your
host paths (the dev stack's `npm run info` does this for you).

### Database commands

| Command | Description |
| ------- | ----------- |
| `db:migrate` | Run all pending migrations (framework-bundled and your own under `app/Database/migrations`). |
| `db:seed` | Run migrations, then load seed data from `app/Database/seed`. |
| `db:status` | Show which migrations have been applied. |
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
(`ZubZet\Framework\Database\Connection::COLLATION`). The command converts **every** table of the
database to it, framework and application tables alike, with
`ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci`:

```bash
php index.php repair:database-collation
```

Only tables whose own collation or any text column's collation differs are converted; a rerun
with nothing left to convert changes nothing. Columns that deliberately use another collation
(e.g. a case-sensitive `utf8mb4_bin` column) are converted as well.

!!! warning
    Converting rewrites the whole table and blocks writes to it meanwhile, which can take a
    long time on large tables. Take a backup and run it in a maintenance window.

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

Add `--cli` to `testing:coverage:stop` to print a text summary instead of generating the HTML report.