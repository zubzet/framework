# Updating to the newest version of the framework
This page assumes the framework is a Composer dependency, as in a project built by the [install script](installation.md).

1. Run `composer update zubzet/framework`. The package has no `version` key, Composer takes versions from the git tags (e.g. `v1.2.0`).
2. Follow the [upgrade guide](upgrade/1.2.0-to-1.3.0.md) for every version you skip.
3. Check the result with `php index.php info:startup`, which prints the installed version (`unknown` if Composer cannot tell).
