# Installation
ZubZet ships as a Composer package. New projects start from the **`zubzet/zubzet`** skeleton, which pulls in the framework and bundles a Docker development stack: Apache + PHP, MariaDB,
phpMyAdmin, and a mail catcher.

## Prerequisites
- **Docker** and **Docker Compose**
- **Composer**
- **Node.js** and **npm**

PHP itself is not required on your host as it runs inside the application container.

## Create a new project
```bash
composer create-project zubzet/zubzet your-folder-name
cd your-folder-name
```

## Start the development stack
```bash
npm run start
```

This single command:

1. Installs the npm dev dependencies,
2. Builds and starts the Docker stack (detached),
3. Runs `composer install` inside the application container,
4. Seeds the database with `db:seed`, which applies all pending migrations

!!! warning "First run"
    The first `npm run start` builds the container image and downloads dependencies, so it takes a few minutes. Subsequent starts are fast.

## Access your application
| Service | URL |
| ------- | --- |
| Application | <http://localhost:8080> |
| Database UI (phpMyAdmin) | <http://localhost:8081> |
| Mail catcher (smtp4dev) | <http://localhost:3300> |

The seed creates demo accounts:
1. `admin@zubzet.com`
2. `support@zubzet.com`
3. `customer@zubzet.com`

(see `app/Database/seed/accounts/`). Adjust or remove them before going to production.

## Everyday commands
| Command | What it does |
| ------- | ------------ |
| `npm run start` | Start the stack and seed the database |
| `npm run stop` | Stop and remove the stack (and its volumes) |
| `npm run restart` | Restart the stack |
| `npm run shell` | Open a shell inside the application container |
| `npm run seed` | Reset the database: run migrations, then load seed data |

## Configuration
Settings live in `z_config/z_settings.ini`. When `allow_env_config = true`, any setting can be overridden by a `CONFIG_*` environment variable. The Docker stack uses this to inject the database and mailer credentials (see `packaging/docker/docker-compose-base.yml`). Keep secrets out of the committed INI file and provide them via the environment.

## Next steps
- [Migrations](../core-features/migrations/index.md) — schema management and the migration CLI.
- [Configuration](../core-features/configuration.md) — every available setting.
- [MVC](../core-features/mvc.md) and [Routing](../core-features/routing.md) — build your first pages.
