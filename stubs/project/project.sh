#!/bin/sh
# Forwards to the project script shipped with the framework
export ZUBZET_PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"

exec sh "${COMPOSER_VENDOR_DIR:-$ZUBZET_PROJECT_ROOT/vendor}/zubzet/framework/scripts/project.sh" "$@"
