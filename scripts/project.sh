#!/bin/sh
# Called via the project's project.sh, which sets ZUBZET_PROJECT_ROOT
root="${ZUBZET_PROJECT_ROOT:?Run this via project.sh in your project root}"

compose() {
    docker compose -f "$root/packaging/docker/docker-compose-base.yml" "$@"
}

case "$1" in
    start)
        compose up --remove-orphans --build -d &&
        docker exec application composer install &&
        docker exec application php index.php db:seed &&
        docker exec application php index.php info:startup --pwd "$root"
        exit
        ;;
    stop)
        compose down -v
        exit
        ;;
    shell)
        exec docker exec -it application bash
        ;;
    tests|cypress)
        # Cypress runs on the host -> requires Node.js
        if ! command -v npm >/dev/null; then
            echo "Error: Node.js is required for '$1' but was not found in your PATH." >&2
            echo "Install Node.js: https://nodejs.org/en/download" >&2
            exit 1
        fi

        # Test dependencies missing -> tell the user how to install them, never install automatically
        if [ ! -x "$root/node_modules/.bin/cypress" ]; then
            echo "Error: Cypress is required for '$1' but was not found in $root/node_modules." >&2
            echo "Install the test dependencies with:" >&2
            echo "npm install --no-save --no-package-lock cypress@^15.2.0 chai@^6.0.1 mocha@^11.7.2 puppeteer@^24.19.0 unicode-substring@^1.0.0" >&2
            exit 1
        fi

        # tests -> headless run, cypress -> interactive UI; extra args (e.g. --spec) are passed through
        [ "$1" = tests ] && mode=run || mode=open
        shift
        exec "$root/node_modules/.bin/cypress" "$mode" --project "$root/tests" "$@"
        ;;
esac

# Check if PHP is available and if the index.php file exists in the root directory -> execute the PHP script directly
if command -v php >/dev/null && [ -f "$root/index.php" ]; then
    exec php "$root/index.php" "$@"
fi

# Interactive terminal -> run in container with TTY
if [ -t 0 ]; then
    exec docker exec -it application php index.php "$@"
fi

# Non-interactive (pipe, CI, cron) -> run in container without TTY
exec docker exec -i application php index.php "$@"

docker compose -f "$directory/packaging/docker/docker-compose-base.yml" up --build -d