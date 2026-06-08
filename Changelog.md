# Changelog
This is a simple changelog to keep track of things that have changed. It is not a substitution for upgrade documentation.

## ToDos
These todos should be as temporary as possible:

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