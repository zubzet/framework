### Console Commands

ZubZet provides a built-in console interface that allows you to manage and interact with the framework directly from the command line.
Console commands enable automation, debugging, and execution of application logic outside the HTTP context.

### How to Run a Command

To execute a console command inside the Docker container, first access the container shell:

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

Executes a controller action directly from the console environment.
This is useful for running application logic, maintenance tasks, or background operations without an HTTP request.

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

### Coverage

Collect a runtime code-coverage report:

```bash
php index.php testing:coverage:start
# ... exercise the app via tests or manual requests ...
php index.php testing:coverage:stop
```

Add `--cli` to `testing:coverage:stop` to print a text summary instead of generating the HTML report.