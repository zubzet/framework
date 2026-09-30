#!/bin/sh
# Forwards to the project script shipped with the framework
ZUBZET_PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
export ZUBZET_PROJECT_ROOT

vendor="${COMPOSER_VENDOR_DIR:-$ZUBZET_PROJECT_ROOT/vendor}"
# Test only: the fork, switch back to "zubzet/framework" once merged
package="qtnoe/zubzet-framework"
script="$vendor/$package/scripts/project.sh"

exec sh "$script" "$@"
