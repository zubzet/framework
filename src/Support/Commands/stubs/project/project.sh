#!/bin/sh
# Forwards to the project script shipped with the framework
ZUBZET_PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
export ZUBZET_PROJECT_ROOT

vendor="${COMPOSER_VENDOR_DIR:-$ZUBZET_PROJECT_ROOT/vendor}"
# Test only: the fork, switch back to "zubzet/framework" once merged
package="qtnoe/zubzet-framework"
script="$vendor/$package/scripts/project.sh"

# Vendor not installed yet (e.g. fresh clone) -> install it via Docker, no local Composer needed
if [ ! -f "$script" ]; then
    # Custom vendor dir may live outside the project -> can't mount it reliably
    if [ -n "$COMPOSER_VENDOR_DIR" ]; then
        echo "Error: $script not found." >&2
        echo "Install the dependencies first: composer install" >&2
        exit 1
    fi

    if ! command -v docker >/dev/null; then
        echo "Error: Docker is required but was not found in your PATH." >&2
        echo "Install Docker: https://docs.docker.com/get-started/get-docker/" >&2
        exit 1
    fi

    echo "Installing dependencies (composer install)..."
    # Same PHP version as packaging/docker/Dockerfile.apache-local
    docker run --rm -u "$(id -u):$(id -g)" -e HOME=/tmp -v "$ZUBZET_PROJECT_ROOT":/app -w /app \
        --entrypoint composer ghcr.io/zubzet/php:8.4-apache install --no-interaction || exit 1

    # Still missing -> composer.json does not require the framework
    if [ ! -f "$script" ]; then
        echo "Error: $script not found after composer install." >&2
        echo "Make sure composer.json requires $package." >&2
        exit 1
    fi
fi

exec sh "$script" "$@"
