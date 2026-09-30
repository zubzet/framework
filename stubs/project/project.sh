#!/bin/sh
# Forwards to the project script shipped with the framework
export ZUBZET_PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"

# Test only: the fork, switch back to "zubzet/framework" once merged
exec sh "${COMPOSER_VENDOR_DIR:-$ZUBZET_PROJECT_ROOT/vendor}/qtnoe/zubzet-framework/scripts/project.sh" "$@"
