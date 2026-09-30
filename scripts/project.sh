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