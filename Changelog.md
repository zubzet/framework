# Changelog
This is a simple changelog to keep track of things that have changed. It is not a substitution for upgrade documentation.

## ToDos
These todos should be as temporary as possible:

## v1.4.0
1. Added organization management to the Z-Admin panel: create organizations with an optional permission group, list and edit them, and assign users to one
1. Added #183 - Multiple forms in one view: every `ZForm` submission carries a `formAction` taken from the new `name` option (falling back to `dom`), and `$req->hasFormData($formAction)` targets a single form. Argument-less calls keep detecting any submitted form
1. Added #178 - Health endpoint at `GET /_zubzet/health`: performs an explicit database check and reports plain JSON without error details. Enabled by default, disable via `health_endpoint_enabled = false`
1. Fixed `--dry` executing PHP migrations in `db:migrate` and `db:sync`. Migration files are no longer loaded during a dry run, so skip and environment markers are not evaluated - every pending migration is reported
1. Added #80 - Cluster-resilient database connection: `Connection::exec()` retries transient contention errors (deadlock, lock-wait timeout, Galera serialization conflict) and recovers dropped connections, including Galera nodes refusing service as SST/IST donor (`1047`), by reconnecting through the endpoint, re-preparing, and re-running the statement. Tunable via `db_max_retries` (default `3`, `0` disables). **Migrator note:** a statement that previously failed on a dropped connection may now be applied twice in the rare case where the server executed a write but the acknowledgment was lost; single statements only, since every `exec()` auto-commits
1. Changed - Database connect attempts are bounded by a 5 second timeout and PHP 8.0 connect failures now throw the same exception as PHP 8.1+, instead of leaving a half-initialized connection behind
1. Added - Encrypted database transport: `db_ssl = true` connects over TLS and verifies the server certificate against the system trust store; a private authority is added to that store or set via `openssl.cafile`. Applies to migrations as well. Documented in [Database Connection](docs/core-features/database-connection.md)
1. Added - Optional persistent database connections via `db_persistent = true`: the PHP worker keeps the connection open across requests and skips the handshake. Off by default, because a worker then stays on the cluster node it first reached. **Migrator note:** mysqli pools by endpoint and credentials, not by transport, so reload PHP-FPM when changing `db_ssl` with this on
1. Changed - `dbport` is now honored by the runtime connection, not only by migrations. With `db_ssl` on, `dbhost` must be a plain hostname (the certificate is matched against it, and no certificate names a port); a `host:port` value is rejected with an error pointing to `dbport`
1. Changed - The e2e suite now runs against a real three-node MariaDB Galera cluster behind a failover proxy, so every test implicitly exercises cluster behavior
1. Added - Module system: Composer packages of type `zubzet-module` contribute controllers, models, views, routes, console commands, migrations, seeds, and webroot assets, resolved through the new central `Registry` (`src/Registry`) with the precedence userspace, then modules (ordered by the `modules` ini key, then Composer installed order), then framework. Includes the `module:setup` command (append-only merge of missing module ini defaults) and module rows in `info:startup`. See the [Modules docs](docs/advanced-features/modules.md)
1. Added - Console commands by convention: `app/Commands/` files (application and modules) declare Symfony commands that register next to the framework's; on a command-name collision the application wins, then modules, then the framework
1. Added - Debug bar "Resolutions" tab: every convention lookup of the request is listed with the root that won it (userspace, module package, or framework) and the resolved file path
1. Bare controller and model name lookups now resolve recursively into subdirectories after a flat miss, shallowest match first. **Migrator note:** previously unreachable nested files under `app/Controllers` and `app/Models` become routable - delete stale copies you do not want served
1. The model instance cache is now keyed by the resolved file path. **Migrator note:** two `getModel` calls that aliased one instance via the `dir` parameter now yield separate instances
1. `db:migrate` and `db:sync` now abort when two migration files share a basename anywhere in the assembled set, userspace subdirectories and framework migrations included (the scan is recursive). **Migrator note:** executed state is keyed on the basename, so one duplicate was previously silently skipped; rename or delete one of the listed files to proceed
1. The asset proxy no longer serves files with `.php`, `.phtml`, or `.ini` extensions. **Migrator note:** this applies to every mount, including sources registered via `registerWebRootSource`

## v1.2.0
1. Added DEV Changelog
1. Add logging folder to .gitignore
1. Replace Slim with FastRoute
1. Added info:startup command
1. Added static arguments in routes, middlewares and afterwares
1. Added coverage report
1. Added `$res->json()` for sending a JSON response
1. Added traceId for request-scoped log correlation (`Logger::getTraceId` / `setTraceId`)
1. Added per-logger context (`contextAdd`, `contextInspect`, `contextMergeFrom`, `contextClear`)
1. Auto-log slow queries, slow requests, warnings, deprecations and uncaught exceptions on the `zubzet` channel
1. Fixed database logger recursion when the slow-query insert itself is slow
1. Preserve `insertId` and `result` across slow-query logging via new `Support\Checkpoint` primitive
1. Classify PHP errors into proper log levels and stable `LogEventType` values (`WARNING`, `NOTICE`, `DEPRECATION`, `PARSE`, …); respect `@` suppression
1. Moved `StreamLogger` and `DatabaseLogger` into `ZubZet\Framework\Logger\Method\`
1. Renamed `LogEventType` constants to `UPPER_SNAKE_CASE` and promoted channel name constants to `Logger::APP` / `Logger::ZUBZET`
1. Added Whoops as error page
1. Fixed #128 - `ZForm` now triggers the unsaved-changes hint on `input` (in addition to `change`), so banners/labels no longer appear *during* a click and shift the target out from under it. `ZCEDItem` likewise wires its inner-field listener to `input` so typing inside a CED row also propagates immediately. `ZForm.send()` is debounced via an `isSending` guard plus a 300 ms minimum window, so a fast double-click submits only once. **Migrator note:** any cypress test relying on `cy.get(button).click().click()` to defeat this bug must be reviewed - with the fix, both clicks now land. Either drop the redundant second click, or, if the second click triggered a separate add-row/CED action that was previously masked by a layout shift (see `tests/cypress/e2e/z-admin/zadmin.cy.js`), the test was passing on accident and its assertions need to be reconsidered (the empty added row is now correctly flagged as invalid).
1. Introduce PHPDebugBar
1. Added optional permission `Group` link to `Organization` (`groupId` column on `z_organization`); `Organization::add()` accepts a `createGroup` flag and exposes `getGroup()` / `refreshGroup()`. `User::updateOrganization()` now syncs the user's group membership when the organization changes (removes the previous org's group, adds the new one).
1. Added `Role::setPermissionsByRole(Role $role)` to replace a role's permissions with another role's permissions in one call (removes current, copies source).
1. `User::add()` now accepts `null` for the `$password` parameter, allowing users to be created without a password (e.g. invite or SSO flows where the credential is set later via `updatePassword()`).
1. Deprecate getZControllers in RequestResponseHandler
1. Password hashing now uses native Argon2id and the `zubzet/password-hash-utilities` dependency has been removed. Existing hashes still verify through a legacy path and upgrade themselves to Argon2id on the next successful login. See the [Password Handling docs](docs/core-features/password-handling.md).
1. Added `password_scheme` and `last_password_rehash_at` columns to `z_user`; existing password rows are marked `legacy`. **Migrator note:** the schema migration runs automatically. Optionally run `php index.php auth:migrate-hashing` to bring dormant legacy rows onto Argon2id before their next login.
1. Added `User::verifyPassword()` (self-healing login check) plus the `Password` and `Verification` API for hashing and verifying outside a `User`. **Migrator note:** `z_loginModel::checkPassword()` is deprecated in favor of these; the existing 3-argument call still works.
1. Deprecate `<#decb64#>`