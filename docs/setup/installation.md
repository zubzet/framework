# Installation
ZubZet projects are set up with an install script. It builds the project and bundles a Docker development stack: Apache + PHP, MariaDB, phpMyAdmin, and a mail catcher.

## Requirement
[**Docker**](https://docs.docker.com/get-started/get-docker/) is required. Nothing else needs to be installed on your host.

## Install script
Save the following script as `install.sh` and run it with `bash install.sh your-folder-name`. Without an argument it asks for the project directory.

```bash
#!/usr/bin/env bash


# ----------------
# Check Environment
# ----------------

if ! command -v docker >/dev/null; then
    echo "Error: Docker is required but was not found in your PATH." >&2
    echo "Install Docker: https://docs.docker.com/get-started/get-docker/" >&2
    echo "Then run this installer again." >&2
    exit 1
fi


# ----------------
# Project Directory
# ----------------

# Given as first argument (./install.sh my-project), otherwise asked for
if [ $# -ge 1 ]; then
    directory="$1"
else
    read -r -p "Project directory (leave empty for current directory): " directory
fi
directory="${directory:-.}"

mkdir -p "$directory"
directory="$(cd "$directory" && pwd)"
echo "Installing ZubZet into $directory"

# The project belongs to the user running the installer, the application container takes over its uid.
# Apache refuses to run as root, so for root the project goes to uid 1000 instead.
owner="$(id -u):$(id -g)"
if [ "$(id -u)" = 0 ]; then
    owner="1000:1000"
    chown "$owner" "$directory"
fi

# ----------------
# Require Framework, Run Install Command
# ----------------

# The framework from source because the dist archive leaves out tests/, everything else as dist
docker run --rm -u "$owner" -e HOME=/tmp -v "$directory":/app --entrypoint sh ghcr.io/zubzet/php:8.5-apache -c '
    mkdir /tmp/installer && cd /tmp/installer &&
    echo "{\"config\":{\"preferred-install\":{\"zubzet/framework\":\"source\",\"*\":\"dist\"}}}" > composer.json &&
    composer require -q zubzet/framework:dev-develop &&
    cd vendor/zubzet/framework/tests/e2e && COMPOSER_VENDOR_DIR=/tmp/installer/vendor php index.php install /app
' || exit 1
```

## Start the project
Inside your project folder, run:

```bash
./project.sh start
```

This single command:

1. Builds the application image and starts the Docker stack (detached),
2. Runs `composer install` inside the application container,
3. Seeds the database with `db:seed`, which applies all pending migrations,
4. Prints the startup info with `info:startup`.

To stop and remove the stack (and its volumes), run `./project.sh stop`. `./project.sh shell` opens a shell inside the application container. See [console commands](../core-features/console-commands.md#projectsh) for all `project.sh` commands.

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

## Configuration
Settings live in `z_config/z_settings.ini`. When `allow_env_config = true`, any setting can be overridden by a `CONFIG_*` environment variable. The Docker stack uses this to inject the database and mailer credentials (see `packaging/docker/docker-compose-base.yml`). Keep secrets out of the committed INI file and provide them via the environment.

## Next steps
- [Migrations](../core-features/migrations/index.md) — schema management and the migration CLI.
- [Configuration](../core-features/configuration.md) — every available setting.
- [MVC](../core-features/mvc.md) and [Routing](../core-features/routing.md) — build your first pages.
