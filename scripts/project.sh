#!/bin/sh
# Called via the project's project, which sets ZUBZET_PROJECT_ROOT
root="${ZUBZET_PROJECT_ROOT:?Run this via project in your project root}"

compose() {
    docker compose -f "$root/packaging/docker/docker-compose-base.yml" "$@"
}

case "$1" in
    start)
        # The application container runs as the project owner, so it can write to the project files
        compose build --build-arg USER_UID="$(ls -nd "$root" | awk '{print $3}')" &&
        compose up --remove-orphans -d &&
        docker exec application composer install &&
        docker exec application php zubzet db:seed &&
        docker exec application php zubzet info:startup --pwd "$root"
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
            echo "npm install" >&2
            exit 1
        fi

        # tests -> headless run, cypress -> interactive UI; extra args (e.g. --spec) are passed through
        [ "$1" = tests ] && mode=run || mode=open
        shift
        exec "$root/node_modules/.bin/cypress" "$mode" --project "$root/tests" "$@"
        ;;
esac

# Project container is running -> run in it, the settings and the database host only exist there
if [ -n "$(compose ps --status running -q application 2>/dev/null)" ]; then
    # Interactive terminal -> with TTY, non-interactive (pipe, CI, cron) -> without
    if [ -t 0 ]; then
        exec docker exec -it application php zubzet "$@"
    fi
    exec docker exec -i application php zubzet "$@"
fi

# Container is not running -> execute the PHP script directly, if PHP is available
if command -v php >/dev/null && [ -f "$root/zubzet" ]; then
    exec php "$root/zubzet" "$@"
fi

echo "Error: The project is not running and PHP was not found in your PATH. Start it with: ./project start" >&2
exit 1
