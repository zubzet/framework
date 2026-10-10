# Documentation drift report

Open items where the docs on `main` already say something, but not enough or something wrong. Generated 2026-10-01 from commit `79f5fc7`.

## Outdated: docs contradict the code (58)

### 1. Installation & Project Structure
- `docs/core-features/controllers-and-actions.md`: Docs name `exampleController.php` for URL `example`; Router::executePath uses ucfirst(segment).'Controller', so file must be `ExampleController.php` on case-sensitive systems (item: Case rule: only the first letter of the URL segment is upper-cased, so file names must match that form on case-sensitive file systems.) //urgent//
- `docs/core-features/migrations/index.md`: Docs say hyphens in seed file names become underscores in the class name; SeedPHP uses the file name unchanged, no conversion (item: PHP seed class: the class name must equal the seed file name without extension.) //urgent//
### 2. Configuration
- `docs/core-features/controllers-and-actions.md`: Docs say a controller named IndexController runs when the URL has no name; code (Router.php) defaults defaultIndex to DashboardController (item: Default: `DashboardController` (code default when the key is unset); the controller class run with `action_index` when the URL has no path s) //urgent//
- `docs/core-features/error-handling.md`: Docs: level 0 is 'PHP's built-in error display'; code sets display_errors off and error_reporting(0), so errors are neither shown nor logged (item: `0` (`NONE`): `display_errors` off and `error_reporting(0)`, so PHP errors are neither shown nor logged; action exceptions become the `error) //important//
- `docs/setup/upgrade/1.1.0-to-1.2.0.md`: Upgrade note says logging defaults to info level; code default for logger_level is notice (logging.md is correct) (item: Default: `notice`; minimum level as a Monolog name (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`, case-in) //important//
- `docs/core-features/access-control.md`: Docs say loginTimeoutSeconds defaults to 7 days; code passes TIMESPAN_DAY_7 as $useDefault, so unset yields 0 and no default applies (item: No effective default: unset evaluates to 0 because `config('loginTimeoutSeconds', TIMESPAN_DAY_7)` passes the fallback as `$useDefault`.) //urgent//
- `docs/forms/file-uploads.md`: Docs say Z-Forms uploads always go to 'uploads/'; code reads the uploadFolder setting (default uploads/), so the target is configurable (item: Default: `uploads/`; directory for files saved by the form-based `Response` insert and update helpers, relative to the working directory and) //important//
### 3. Application Lifecycle & Bootstrap
- `docs/contributing/agents/working-with-agents.md`: Docs' 'current sequence' starts at self::$instance; code runs Collector::initialize() first and DebugBarBridge::bootstrap() (omitted) before User (item: Coverage collector: `Collector::initialize()` runs first, only starts coverage when a `.coverage.session` file exists and then registers a s) //should-do//
### 4. Routing
- `docs/core-features/controllers-and-actions.md`: Docs name the file `exampleController.php` for URL /example; code applies ucfirst, so on Linux the file must be `ExampleController.php` (item: Controller name: `ucfirst(segment 1) . 'Controller'` with the case otherwise preserved, so the file name is case-sensitive on Linux.) //urgent//
- `docs/core-features/controllers-and-actions.md`: Docs say IndexController runs when no controller is given; code runs the `defaultIndex` setting, defaulting to DashboardController (Router.php:189) (item: Empty path: runs the `defaultIndex` controller (default `DashboardController`) with `action_index`, and a missing segment 2 also means `acti) //urgent//
### 6. Request & Response
- `docs/core-features/permission-system.md`: Docs say all users lacking the permission go to the 403 page; code sends anonymous (not logged in) users to login/index instead (item: `checkPermission(string $permission, bool $boolResult = false, bool $includeSuperUser = false)`: sends anonymous users to `login/index`, oth) //important//
- `docs/core-features/rest-api.md`: Docs say generateRest with result=error is auto-converted into a REST Error; code only wraps payload in the meta envelope, no conversion (item: `generateRest(array $payload, bool $die = true)`: echoes a pretty-printed `Rest` envelope (`meta` plus payload keys) and exits unless `$die`) //important//
- `docs/forms/file-uploads.md`: Docs say file() uploads always go to 'uploads/'; Response::uploadFromForm reads the `uploadFolder` setting (default uploads/) (item: Upload rules used: `fileMaxSize` and `types` of the field rules are passed to the upload, and the target folder comes from the `uploadFolder) //important//
### 8. Helpers & Utilities
- `docs/core-features/global-helper-functions.md`: Docs show config($key, useDefault=false); code has optional key, default true (item: Purpose: proxy to `zubzet()->getBooterSettings()` for reading settings, whose semantics are described in Configuration.) //important//
- `docs/core-features/global-helper-functions.md`: Docs show config(string $key, bool $useDefault = false); code is config($key = null, $useDefault = true, ...), so a missing key returns null, not throws (item: Missing key: returns `$default` (null unless given) when `$useDefault` is truthy, otherwise throws `InvalidArgumentException`.) //urgent//
- `docs/core-features/access-control.md`: Docs claim config('loginTimeoutSeconds') defaults to 7 days; the TIMESPAN_DAY_7 argument lands in $useDefault, so no default is supplied (item: Argument order trap: `config("loginTimeoutSeconds", TIMESPAN_DAY_7)` passes the fallback as `$useDefault`, so it does not supply a default.) //urgent//
- `docs/core-features/access-control.md`: Docs say the 7-day fallback applies for loginTimeoutSeconds; TIMESPAN_DAY_7 can never take effect because of the config() argument order (item: `TIMESPAN_DAY_7`: 604800 seconds, which `Session::expiresAt()` intended as the `loginTimeoutSeconds` fallback but cannot apply because of th) //important//
- `docs/core-features/rest-api.md`: Docs say the REST pipeline lives in `z_rest.php`; no such file exists, it is the `Rest` class (src/Support/Rest.php, alias Rest) (item: Purpose: builds and sends a pretty-printed JSON payload, and `Response::generateRest()` and `generateRestError()` wrap it.) //important//
### 9. CLI & Console Commands
- `docs/core-features/migrations/index.md`: Docs show `php zubzet migrate:import` and `db:unlock-migrations`; real commands are `php index.php db:migrate` and `db:unlock-migration` (item: Command names: `run`, `info:startup`, `db:migrate`, `db:status`, `db:sync`, `db:seed`, `db:unlock-migration`, `auth:migrate-hashing`, `testi) //urgent//
- `docs/core-features/migrations/index.md`: Docs call it a bypass letting skipped framework migrations proceed; code makes the skipped-migration check also apply to framework migrations (item: Option `--enforce-external-timeline`: applies the skipped-migration check to framework migrations too, which is normally exempt.) //urgent//
- `docs/core-features/console-commands.md`: Docs say db:status shows which migrations have been applied; code only prints one 'Migration Lock Status' line and lists no migrations (item: Output: one line `Migration Lock Status: LOCKED` or `Migration Lock Status: UNLOCKED`; it does not list pending or executed migrations.) //important//
- `docs/core-features/console-commands.md`: Docs say --environments-included=Dev seeds only Dev; code treats selectors as paths, all files start selected, so -i alone changes nothing (item: Include without exclude: `-i` alone does not narrow the run because every file starts selected.) //important//
### 10. Database & Query Builder
- `docs/contributing/agents/working-with-agents.md`: Docs say Connection::exec records the calling model; code: only Model::exec sets Connection::$callingModel, Connection::exec just passes it to the debug bar (item: Tag source: `Connection::$callingModel` is set only by `Model::exec()` and handed to `DebugBarBridge::collectQuery()` with every `exec()`.) //important//
- `docs/core-features/debug-bar.md`: Docs say direct db()->exec() calls are always shown; callingModel is never reset, so they reuse the previous model's internal tag and can be hidden (item: Stale tag: `callingModel` is never reset, so a later `db()->exec()`, `execQuery()` or the `Model::getFullTable()`, `getTableWhere()` and `co) //important//
- `docs/core-features/query-builder/index.md`: Docs show getQueryBuilder() returning db()->cakePHPDatabase; that property does not exist, code returns db()->queryBuilderConnection (item: `getQueryBuilder()`: Returns `db()->queryBuilderConnection` (the `Cake\Database\Connection`), shared by all models.) //important//
- `docs/setup/upgrade/1.1.0-to-1.2.0.md`: Docs call 2026-03-27_session-handling.sql idempotent, re-applies safely; its ADD COLUMN statements lack IF NOT EXISTS, so a re-run fails (item: `2026-03-27_session-handling.sql`: Adds `z_logintoken.extended_seconds` and `active`; not re-runnable.) //important//
### 11. Migrations & Seeding
- `docs/core-features/migrations/index.md`: Docs show a timestamp column with default CURRENT_TIMESTAMP; custom type actually emits quoted 'CURRENT_TIMESTAMP' (verified), so columnDefinition is needed (item: Defaults: The `timestamp` type adds no default value; use the `columnDefinition` option (for example `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`).) //important//
- `docs/core-features/migrations/index.md`: Docs call --enforce-external-timeline a bypass allowing skipped framework migrations; code exempts them by default and the flag enforces the check (item: Bundled exemption: Bundled migrations are exempt from skipped detection (they still run when pending) unless `--enforce-external-timeline` i) //important//
- `docs/core-features/migrations/index.md`: Safety section says import aborts on skipped migrations unless configured; code defaults --force to true, so only a warning is printed (item: `--force` default: Enabled by default, so skipped files only produce a warning and are executed as pending in order.) //urgent//
- `docs/core-features/console-commands.md`: Docs say -i=Dev restricts the seed run to that environment; code: -i alone changes nothing, it only re-adds files removed by -e (item: Filter edge cases: `-i` alone changes nothing (it only re-adds excluded files) and an empty selector matches nothing.) //important//
- `docs/core-features/migrations/index.md`: Docs say hyphens in seed file names become underscores in the class name; SeedPHP uses the file name unchanged as class name (item: PHP seed class: The class name equals the filename without extension, lives in the global namespace, extends `ZubZet\Framework\Database\Migr) //urgent//
- `docs/core-features/migrations/index.md`: Docs say $this->dbInsert() registers the query automatically; code: dbInsert only builds it, only Seed::insert() queues it via addQuery (item: `Seed::insert($table, array $data)`: Queues one `dbInsert()` query.) //urgent//
### 12. Authentication, Sessions & Passwords
- `docs/core-features/access-control.md`: Docs say loginTimeoutSeconds defaults to 7 days; Session::expiresAt passes TIMESPAN_DAY_7 as $useDefault, so an unset setting gives lifetime 0 (item: Missing default: `config("loginTimeoutSeconds", TIMESPAN_DAY_7)` passes the fallback as `$useDefault`, so an unset setting gives lifetime 0 ) //urgent//
- `docs/z-admin/login-as-another-user.md`: Docs point to the Log / Statistics panel for tracking; that viewer was removed, loginAs now writes USER_LOGGED_IN(_ANOTHER) log events (item: Logging: Writes `USER_LOGGED_IN` when user and exec user match, otherwise `USER_LOGGED_IN_ANOTHER`.) //important//
- `docs/core-features/access-control.md`: Docs declare email(): ?string; code is email(): string and throws TypeError for users with a NULL email (item: `$user->email(): string`: Returns the cached email and throws a `TypeError` for users whose email is NULL because of the `string` return typ) //important//
### 13. Permissions, Roles, Groups & Organizations
- `docs/core-features/access-control.md`: Docs build instances via (new User())->loadObject($data); constructor requires array $data with an id, and loadObject returns void (item: Constructor: `__construct(array $data)` takes a raw database row and requires an `id` key.) //important//
- `docs/core-features/access-control.md`: Docs show checkInstance(): bool; code returns nothing and throws InvalidArgumentException 'Instance no longer exists'; nullId() undocumented (item: `nullId()` and `checkInstance()`: After `nullId()` every method throws `InvalidArgumentException` "Instance no longer exists".) //important//
- `docs/core-features/access-control.md`: Docs list $role->refreshUsers() as public API; in code Role::refreshUsers() is private (only Organization::refreshUsers is public) (item: `refreshUsers()` [internal]: Private method that reloads the user cache via `User::byRole()`.) //important//
### 14. Included Pages & Admin Dashboard
- `docs/core-features/controllers-and-actions.md`: Docs say IndexController runs when no name is given; Router uses setting defaultIndex, defaulting to DashboardController (item: Root route: It serves `/` only when `defaultIndex = IndexController` is configured; without the setting `/` targets `DashboardController`.) //urgent//
- `docs/core-features/permission-system.md`: Docs say all users lacking the permission get the 403 page; code sends anonymous users to login/index and only logged-in ones to 403 (item: 403 variants: `checkPermission("console")` outside CLI also renders 403, and with `boolResult: true` these checks return `false` instead; an) //important//
- `docs/core-features/permission-system.md`: Docs route every user without the permission to 403; checkPermission renders login/index for anonymous visitors (item: Anonymous visitor: The login page (`login/index`) is rendered in place and execution stops.) //important//
- `docs/guides/library.md`: Guide claims default admin admin@zierhut-it.de/password; no bundled migration creates an admin (only the e2e test seed does) (item: No default admin: No bundled migration inserts an admin role, user or permission, so grants must be created manually in `z_role_permission` ) //urgent//
- `docs/z-admin/usage.md`: Docs list categories Instance, Log/Statistics, Framework Update; dashboard has Application, Users and Roles, Other sections (item: Layout: Icon-only cards in the sections Application, Users and Roles, and Other.) //important//
- `docs/z-admin/usage.md`: Docs list Instance, Log/Statistics, Framework Update; Application section only has Database and Maintenance cards (item: Application cards: Database (`admin.database`) and Maintenance (`admin.maintenance`).) //important//
- `docs/z-admin/login-as-another-user.md`: Docs say use the Log / Statistics panel; it no longer exists, loginAs writes USER_LOGGED_IN_ANOTHER (or USER_LOGGED_IN) via logger (item: Cookie and logging: A new `z_login_token` replaces the old one, and `USER_LOGGED_IN_ANOTHER` (or `USER_LOGGED_IN` when both ids are equal) i) //important//
- `docs/z-admin/usage.md`: Docs describe Instance, Log/Statistics, Framework Update entries; the sidebar has Application, Users and Roles (incl. Groups) and Other (item: Entries: Application (Database `admin.database`, Maintenance `admin.maintenance`), Users and Roles (Edit User `admin.user.edit`, Add User `a) //important//
### 15. Forms, Validation & File Uploads
- `docs/forms/file-uploads.md`: Docs say Z-Form uploads are always moved into 'uploads/'; code uses the `uploadFolder` setting, only defaulting to uploads/ (item: Target: Uploads into the `uploadFolder` setting (default `uploads/`) and stores the resulting `z_file` id as the field value.) //important//
### 16. Frontend Integration
- `docs/core-features/rest-api.md`: Docs say generateRest with result=error is auto-converted to a REST error; code never converts, $res->error emits meta plus {result:error, message} (item: `error` result: `$res->error($message = '')` returns `{result: error, message}` and `ZForm` shows the `Z.Lang.saveError` hint without using ) //important//
- `docs/forms/auto-form-validation.md`: Docs say required=true means input must be filled before submission; Z.js only adds a red asterisk and input-required class, no client validation (item: `required`: Only adds a red asterisk and the class `input-required` to the label; no client-side validation is performed.) //urgent//
### 17. Logging, Error Handling & Debug Bar
- `docs/core-features/logging.md`: Example registers a plain Monolog\Logger and calls logger('audit'); register() and logger() return the ZubZet Logger type, so a TypeError is raised (item: Return type: The parameter is typed `Logger|LoggerInterface` but `register()` and `logger()` return `Logger`, so a non-ZubZet logger raises ) //urgent//
- `docs/setup/upgrade/1.1.0-to-1.2.0.md`: Upgrade guide says logging defaults to info level; code default logger_level is notice (logging.md correctly says notice) (item: Names and values: Case-insensitive `debug` 100, `info` 200, `notice` 250, `warning` 300, `error` 400, `critical` 500, `alert` 550 and `emerg) //important//
- `docs/core-features/logging.md`: Value example shows a top-level environment key; code has none, userId/execUserId/source are merged into extra (item: Top-level keys: `message`, `context`, `level`, `level_name`, `channel`, `datetime` and `extra`.) //important//
- `docs/core-features/logging.md`: Docs put userId, execUserId and source in a separate environment object; code merges them into extra (extra.userId, extra.execUserId, extra.source) (item: Environment keys: `extra.userId`, `extra.execUserId` and `extra.source` (`cli` or `web`) are appended at write time and override same-named ) //important//
- `docs/core-features/error-handling.md`: Docs say showErrors=0 means PHP's built-in error display; code sets display_errors=0 and error_reporting(0), so nothing is shown (item: Display settings: Sets `display_errors` and `display_startup_errors` to the state value and `error_reporting` to `E_ALL` for `1` and `0` for) //urgent//
- `docs/core-features/logging.md`: Docs show appended environment info as a top-level environment key; appendEnvironment merges it into extra (item: `appendEnvironment(&$logRecord)`: Merges `extra.userId`, `extra.execUserId` and `extra.source` (`cli` or `web`) into the record.) //important//
- `docs/core-features/logging.md`: Docs describe pre-encoding value normalization (DateTime to ISO-8601, objects to class name); code only runs json_encode with four flags, no normalization (item: JSON encoding: Flags `JSON_UNESCAPED_UNICODE`, `JSON_UNESCAPED_SLASHES`, `JSON_INVALID_UTF8_SUBSTITUTE` and `JSON_THROW_ON_ERROR`; empty `co) //important//
### 19. Testing, CI & Contributing
- `docs/contributing/how-to-release.md`: Says e2e runs on push to any branch; workflow filter '*' skips branch names containing '/' (e.g. feature/x) (item: Branch pattern: the filter `'*'` does not match branch names containing `/`.) //nice-to-have//
- `docs/contributing/how-to-release.md`: Says full 8.0-8.5 matrix runs on any branch push; workflow runs it only on dispatch, main, develop, v* tags, else 8.0+8.5 (item: PHP matrix: the full `8.0-apache` to `8.5-apache` on dispatch, pushes to `main` or `develop` and `v*` tags, otherwise only `8.0-apache` and ) //nice-to-have//

## Partially documented (737)

### `docs/advanced-features/aliases-and-virtual-links.md` (1)
- `Response::reroute($path, $alias, $final)`: the controller-facing wrapper, see Request & Response for its parameters.: missing Third parameter $final (execute and exit) and defaults not documented //should-do//

### `docs/advanced-features/commands.md` (8)
- Working directory rule: all default paths are relative to the process working directory, so entry scripts must `chdir()`: missing chdir advice for cron given; not stated that all default paths are cwd-relative //important//
- Boot sequence: calls `chdir(realpath(__DIR__))`, requires `<vendor>/autoload.php`, then runs `new z_framework()` and `->: missing chdir(realpath(__DIR__)) advised; autoload require and new z_framework()->execute() not shown //important//
- Working directory: relative paths such as `./app/Database/...` resolve against the working directory, so run from the pr: missing chdir hint only; relative ./app/Database paths resolving against working directory not explained //important//
- No app command discovery: `$automaticallyLoadedCommands` is an empty placeholder array, so apps cannot add their own Sym: missing Says controllers are used; not that apps cannot register own Symfony commands //important//
- `controller`: required controller name without the `Controller` suffix, such as `dashboard`.: missing Only <controller> placeholder; required, name without Controller suffix, lower-cased not stated //important//
- `action`: optional action name without `action_`, default `index`.: missing Only <action> placeholder; optional, default index, no action_ prefix not stated //important//
- `parameters`: optional array taking every remaining token, passed on as additional URL parts.: missing Only '<param1> ...' placeholder; array of all remaining tokens passed as URL parts not stated //important//
- Working directory: All `./app/...` paths resolve against the process working directory.: missing chdir hint only; ./app/... paths resolving against the working directory not stated //should-do//

### `docs/contributing/agents/working-with-agents.md` (58)
- MariaDB-style syntax: bundled migrations use `ADD COLUMN IF NOT EXISTS`, `ADD INDEX IF NOT EXISTS` and `DROP COLUMN IF E: missing IF NOT EXISTS rule given; no MariaDB-syntax requirement or mariadb:10.5.22 e2e version //urgent//
- `src/IncludedComponents/controllers/`: `ErrorController`, `IndexController`, `LoginController` and `ZController` are use: missing Bundled controllers mentioned; controller names and app-file-first fallback not stated //should-do//
- `src/IncludedComponents/models/`: `z_*Model` classes are used when the app has no model file of that name; an app file w: missing Bundled models mentioned; z_*Model classes and app-file-wins precedence not stated //should-do//
- `src/IncludedComponents/routes/`: framework route files such as `DefaultRoutes.php` are loaded after the app route files: missing Bundled routes mentioned; DefaultRoutes.php and loaded-after-app-routes order not stated //should-do//
- `src/IncludedComponents/views/`: bundled views, layouts and mail templates act as fallback; an app file with the same re: missing Bundled views mentioned; fallback role and same-relative-path app precedence not stated //should-do//
- `src/IncludedComponents/database/Migration/`: nine framework-owned ("external") migrations from `2021-02-04_zubzet.sql` : missing Framework migration location stated; nine files, date range and joint run not stated //should-do//
- `loadConfiguration(string $frameworkRoot, array $params)`: public method of the `Configuration` trait; `$frameworkRoot` : missing only named in boot order; signature and $frameworkRoot becoming z_framework_root missing //nice-to-have//
- Definition: required, no default; public base URL with scheme and optional port and no trailing slash, e.g. `http://loca: missing calls it configured base URL only; required, no default, no trailing slash missing //important//
- Two phases: the constructor boots all services, then `execute()` dispatches to explicit routes, convention routing or th: missing Boot order documented; execute() dispatch (routes, convention, console) not described //should-do//
- Constructor `__construct(array $params = [])`: runs the full boot sequence below, and `$params` override existing settin: missing Boot sequence listed; $params overriding existing settings not mentioned //should-do//
- Latest instance holder [internal]: `ZubZet::$instance` is set at the very start of the constructor and overwritten by ev: missing Instance assignment shown; not noted that every new instance overwrites it //optional//
- Composed traits: `Router`, `Configuration`, `CanRetrieveModel`, `ExceptionBehavior` and `CanRetrieveBooterSettings`, who: missing Trait names appear in src map; not stated they compose ZubZet and are callable on zubzet() //should-do//
- `new Constants` [internal]: the first autoload of `Core/Constants.php` defines the global constants; the class itself is: missing Autoload-defines-constants mechanism and empty class not stated //optional//
- Failures before error handling: errors thrown by configuration or the maintenance gate occur before `setExceptionBehavio: missing Boot order documented; not stated that config/gate errors bypass Whoops and logger //should-do//
- Function conflict: defining a guarded helper function before boot throws `RuntimeException` from the `GlobalReferences` : missing Helpers guarded against redeclaration; RuntimeException on pre-boot conflict not stated //nice-to-have//
- Web versus CLI detection: `isCli()` and `Request::isCli()` test `php_sapi_name() === 'cli'`.: missing isCli() as php_sapi_name()==='cli' stated; Request::isCli() not mentioned //should-do//
- Creation order: `Request` and `Response` are built after configuration, gate, slow-request hook, exception behaviour, as: missing Order listed; DebugBarBridge::bootstrap() step between Connection and User missing //nice-to-have//
- Dispatch order: explicit routes (FastRoute) are tried first, convention routing or the CLI console is the fallback.: missing CLI console fallback and explicit-routes-first order only hinted as 'opt-in override' //important//
- Route precedence: explicit routes always win over convention routing, and user route files load before the bundled `Defa: missing Only implied by 'opt-in override'; user routes loading before DefaultRoutes.php not stated //important//
- Calling model tracking: `exec()` sets `callingModel` on the connection, which the debug bar uses to hide internal models: missing Property callingModel not named; Model::exec (not Connection::exec) sets it //nice-to-have//
- `isCli()`: returns the global `isCli()` result (`php_sapi_name() === "cli"`).: missing Only global isCli() documented, not Request::isCli() delegating to it //nice-to-have//
- No key: returns the array of all settings, including credentials.: missing no-key return only in agents table; credentials warning absent; config() page shows key required //nice-to-have//
- Conflict error: an already existing function throws `RuntimeException` (`The function '<name>' is already defined, but i: missing only 'can't be redeclared'; RuntimeException message and package-removal hint missing //should-do//
- `FunctionConflictResolution::requireAndThen(string $name, callable $declaration)` [internal]: runs the declaration closu: missing closure runs only when function_exists is false; signature not described //optional//
- Availability: declared by the autoload of `new Helpers` late in the `ZubZet` constructor.: missing `new Helpers` listed in boot order without saying it declares the helpers //nice-to-have//
- Loading: defined with `const` in the global namespace when the empty `Constants` class is autoloaded during boot.: missing `new Constants` at boot named; global-namespace const mechanism and full list missing //optional//
- CLI detection: the console starts only when `php_sapi_name() === "cli"` (`isCli()`, `Request::isCli()`) and no FastRoute: missing isCli() documented; 'no FastRoute route matched first' condition and Request::isCli() missing //should-do//
- Usage in the repository: `npm run seed`, `cy.dbSeed()` and both CI workflows call `php index.php db:seed` to prepare the: missing npm run seed and cy.dbSeed documented; both CI workflows calling db:seed missing //optional//
- Creation time: `ZubZet::__construct` builds `new Connection` after configuration, maintenance gate and request/response : missing Debug bar bootstrap between Connection and User missing from listed sequence //nice-to-have//
- `$callingModel`: Nullable `Model` that issued the query, used for debug bar tagging, marked `#[IncludeInCheckpoint]`.: missing Property $callingModel (nullable, checkpoint attribute) not named; tagging attributed to Connection //optional//
- Tagging: Sets `callingModel` to the model, then forwards to `Connection::exec()` or `execQuery()`.: missing Model::exec sets callingModel then forwards; docs attribute tagging to Connection::exec //optional//
- Idempotency: The base migration uses `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`, so it can be re-run safely.: missing Base migration's own CREATE IF NOT EXISTS/INSERT IGNORE re-runnability not stated //nice-to-have//
- Bundled migrations: `IncludedComponents/database/Migration` inside the framework holds the "external" migrations, is nev: missing Location and --exclude-external documented; never auto-created and same-timeline merge missing //important//
- Role: Request-scoped identity built once per request in the framework constructor, whose constructor calls `identify()` : missing Only 'new User' in bootstrap order; identify() reading the session cookie not stated //important//
- Framework dependency: `logger()` needs the framework instance because the first call reads `config()`; calling it before: missing logger() before new ZubZet throws NotInstantiatedException not stated //nice-to-have//
- `getOrCreateLogger(string $name): Logger`: Returns the cached channel or builds it from the `logger_*` settings.: missing getOrCreateLogger() semantics (cached or built from logger_* settings) not described //should-do//
- Debug bar first: The collector is attached via `DebugBarBridge::collectLogger` before any handler is pushed.: missing Collector attached before any handler is pushed not stated //optional//
- Override: Public method (e.g. `zubzet()->setExceptionBehavior(0)`) that overrides `showErrors` for the rest of the reque: missing setExceptionBehavior($state) overriding showErrors for the rest of the request not stated //important//
- Repeated calls: It is called once without argument from the constructor and may be called again at runtime, re-registeri: missing Re-callable at runtime, re-registering error and exception handlers, not stated //nice-to-have//
- `DebugBarBridge::bootstrap()` [internal]: Creates a `StandardDebugBar` plus the `queries`, `templates` and `monolog` col: missing setHideEmptyTabs(true) not mentioned //optional//
- Assets: php-debugbar's `src/DebugBar/Resources` is mounted into the asset proxy and the renderer base URL is the hard-co: missing Resources dir mount and hard-coded /_zubzet/asset-proxy base URL (no rootDirectory) not stated //nice-to-have//
- Static API: `isEnabled(): bool`, `renderHead(): string` and `renderBody(): string`, where both render methods return `"": missing isEnabled() and empty-string return of render methods when disabled not stated //nice-to-have//
- `collectQuery(string $sql, float $durationSeconds, int $rowCount, array $values, ?Model $model = null)`: Feeds the `quer: missing Parameter signature and `queries` collector name not stated //optional//
- `collectTemplate(string $name, array $data, string $type, string $layout)`: Feeds the `templates` collector for every re: missing collectTemplate signature and type 'php' not stated //optional//
- `CanFormatValue` trait [internal]: Strings stay as is, scalars and null use `var_export`, and arrays or objects become p: missing var_export for scalars/null and pretty JSON for arrays/objects not described //optional//
- Debug bar: php-debugbar `src/DebugBar/Resources` at the URL root, registered only when `execution_type` is `test`.: missing Bridge wires asset proxy in test only; php-debugbar Resources mount at root not described //nice-to-have//
- App URL: the e2e app is served at `http://localhost:8080`, not `:4000`, although `z_settings.ini` says `host = http://lo: missing CONFIG_HOST env override of host=http://localhost:4000 not mentioned //optional//
- `cypress`: opens the interactive runner with `cypress open --project tests`.: missing only 'Open Cypress UI'; command cypress open --project tests not stated //nice-to-have//
- `tests`: headless run `env -u ELECTRON_RUN_AS_NODE cypress run --project tests`.: missing command env -u ELECTRON_RUN_AS_NODE cypress run --project tests not stated //nice-to-have//
- Service `application`: container name `application`, image `docker-application:latest`, published port `8080:80`, `resta: missing only port 8080; image, restart policy, healthy-database dependency not stated //nice-to-have//
- `cy.fillForm(inputs)`: types into inputs, or uses `select` for select elements, for each name and value pair.: missing only 'bulk-fill a form'; select handling and name/value pairs not described //nice-to-have//
- `cy.loginAs(profile)`: lower-cases the profile, reads `fixtures/logins.json` and sets the cookie `z_login_token` inside : missing lower-casing, z_login_token cookie and cy.session usage not stated //nice-to-have//
- `cy.http(method, endpoint, body, callback)`: calls `/api/v1/<endpoint>` with header `X-API-KEY: 1234` and `failOnStatusC: missing /api/v1/<endpoint> path, failOnStatusCode false, string body as fixture name missing //nice-to-have//
- `cy.setConfigSetting(key, value)`: rewrites the matching line in `../z_config/z_settings.ini`, which works because the I: missing line-rewrite behaviour and 'INI re-read on every request' not stated //nice-to-have//
- `cy.dbSeed()` (`support/database.js`): runs `docker exec application php index.php db:seed` and clears all saved Cypress: missing Docs say npm run seed; support/database.js and direct docker exec not stated //nice-to-have//
- `fixtures/logins.json`: profiles `wrong`, `not_activated`, `admin`, `support`, `customer` and `customer_new` with names : missing profile names, passwords and fixed 40-character tokens not listed //optional//
- Spec conventions: specs call `cy.dbSeed()` in `before`, use `data-test` attributes and drive CLI commands through `cy.ex: missing cy.dbSeed() in before and data-test convention not stated as spec convention //nice-to-have//
- Setting changes: Cypress commands `cy.setConfigSetting`, `cy.saveConfigBackup` and `cy.restoreConfigBackup` switch logge: missing use for switching logger, maintenance and debug bar settings not stated //optional//

### `docs/contributing/how-to-contribute.md` (3)
- Reference consumer project: `tests/e2e/` shows a complete project with `index.php`, `webroot/`, `z_config/`, `app/` and : missing tests/e2e called dockerized dev app; not described as reference project with its layout //should-do//
- `start`: removes `composer.lock`, runs `npm install`, brings the stack up with `--remove-orphans --build -d`, runs `comp: missing composer.lock removal and --remove-orphans flag not stated //nice-to-have//
- `stop`: runs `docker compose down -v`, which also deletes the database volume.: missing States docker compose down -v but not that DB volume is deleted //nice-to-have//

### `docs/contributing/how-to-release.md` (5)
- Version source: the package has no `version` key in `composer.json`, so versions come from git tags such as `v1.2.0`.: missing Release tags described; not stated that composer.json has no version key //nice-to-have//
- Triggers: `pull_request` (opened, reopened, synchronize, ready_for_review), `push` on branches and tags `v*.*.*`, and `w: missing pull_request event types and workflow_dispatch trigger not stated //nice-to-have//
- Triggers: push to `main` touching `docs/**`, `mkdocs.yml` or the workflow, pushes of tags `v*.*.*`, and `workflow_dispat: missing docs/**, mkdocs.yml path filter and workflow_dispatch trigger not stated //nice-to-have//
- Versioning rules: a tag deploys its version via `mike` and, when promoted, also the alias `latest` with `mike set-defaul: missing dispatch with promote_latest=false publishes version without latest alias //optional//
- Branch builds: `main` or any other branch deploys the alias `unstable`, and normal tag pushes always promote `latest`.: missing any other branch (manual dispatch) also deploying unstable not stated //optional//

### `docs/core-features/access-control.md` (69)
- Definition: seconds a login lasts; sets the expiry of the `z_login_token` cookie in `loginAs()` and the base lifetime in: missing base session lifetime stated; z_login_token cookie expiry from it not stated //important//
- Soft delete: Most tables carry `active` (`TINYINT(1) NOT NULL DEFAULT 1`, `INT` in `z_email_verify`) and are deactivated: missing Cross-table soft-delete convention, active column spec and INT in z_email_verify not stated //should-do//
- `verified`: `TIMESTAMP NULL DEFAULT NULL`, e-mail verification time where NULL means the account is not activated.: missing z_user.verified column (TIMESTAMP NULL, NULL = not activated) not named //should-do//
- `active`: `TINYINT(1) NOT NULL DEFAULT 1` after `verified` (since 2025-11-06), the soft-delete flag.: missing Column type TINYINT(1) DEFAULT 1 and position not stated //extra-effort//
- `name`: `VARCHAR(255) NULL DEFAULT NULL`, not unique.: missing Name not unique stated; VARCHAR(255) NULL DEFAULT NULL type absent //extra-effort//
- `active`, `created`: `TINYINT(1) NOT NULL DEFAULT 1` and `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()`.: missing created column and active/created types and defaults not stated //extra-effort//
- `extended_seconds`: `INT NULL DEFAULT NULL` after `userId_exec`, extra session lifetime (since 2026-03-27).: missing Column type INT NULL DEFAULT NULL and position after userId_exec absent //extra-effort//
- `created`: `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()`, the base of the absolute session lifetime.: missing Expiry base stated; TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP type absent //extra-effort//
- Row lifecycle: Rows are never deleted; logout, expiry, password change and `clearSessions()` only set `active = 0`.: missing Rows never deleted, only active=0 on logout/expiry/password change not stated //should-do//
- Role: Wrapper around one `z_logintoken` row extending `AuthenticationObject` with `HandleTrait` and `RetrievalTrait`.: missing AuthenticationObject base class and HandleTrait/RetrievalTrait not mentioned //should-do//
- `Session::add(User $user, ?User $userExec = null): Session`: Creates the token row (`$userExec` defaults to `$user`) but: missing Not stated that no cookie is set //important//
- `Session::byToken(string $token): ?Session`: Returns the active session or null for unknown or inactive tokens; an expir: missing Only active sessions found; expired-but-not-invalidated session still returned, not stated //important//
- `Session::byUser(User $user): array`: All `active = 1` sessions of a `Permission\User`, including expired ones not yet i: missing Includes expired not-yet-invalidated sessions; empty array when none, not stated //important//
- Inherited finders: `Session::all()`, `byId(int|string)` and `byIds(int ...$ids)` return only `active = 1` rows.: missing Not stated that all/byId/byIds return only active = 1 rows //should-do//
- Getters: `token()`, `userId()`, `userIdExec()`, `extendedSeconds()` (null if never extended), `created()` (DB timestamp : missing id(), getAll() and cached-without-reload behaviour not mentioned //should-do//
- `expiresAt(bool $refresh = true): ?string`: Returns `created + loginTimeoutSeconds + extended_seconds` as `Y-m-d H:i:s`,: missing expiresAt() method, Y-m-d H:i:s format and absolute non-sliding lifetime not stated //important//
- `invalidate()`: Sets `active = 0` and nulls the object, so any further call throws `InvalidArgumentException("Instance n: missing active = 0, nulled object and InvalidArgumentException 'Instance no longer exists' not stated //important//
- Session shape: A login-as session has different `userId` and `userId_exec`; the admin `login_as` action passes the origi: missing Admin login_as passing original execUserId so nested impersonation keeps real identity not stated //should-do//
- Email: `$email` may be `null`; a duplicate email is rejected by the `uq_user_email` unique key.: missing Duplicate email rejected by uq_user_email unique key not stated //should-do//
- `User::byEmail(string $email): ?User`: Finds the active user with that non-NULL email, or null.: missing Only active users with non-NULL email are matched; not stated //important//
- `User::byNotVerified(DateTime $since): array`: Active users whose `verified` is NULL or later than `$since`.: missing Only active users; verified NULL or later than $since not stated //should-do//
- `$user->verify(?DateTime $date = null)`: Sets `verified` to the given date or now, overwriting an earlier value, and cle: missing Overwrites earlier verified value and clears the object's fields, not stated //important//
- `$user->isVerified(string $at = "NOW"): bool`: True when `verified` is set and not later than `$at` (any `strtotime` str: missing Any strtotime string accepted; future verified dates count as unverified now //important//
- `$user->updateEmail(?string $email)`: Updates only active rows, accepts `null`, does no format validation, neither reset: missing Active rows only, no validation, verified and sessions untouched, fields cleared //should-do//
- `$user->remove()`: Soft-deactivates the user (`active = 0`) and nulls the object, but does not clear sessions or reset c: missing Object is nulled; sessions and reset codes are not cleared //urgent//
- Reload after mutation: `updateEmail`, `updatePassword`, `verify` and role/group changes clear the object's fields, so ca: missing Mutations clearing fields, so refresh() or User::byId() is needed, not stated //important//
- `ZubZet\Framework\Authentication\Permission\User`: Database-backed user object over `z_user` using traits `Permission`, : missing Backing table z_user and traits Permission/RetrievalTrait/HandleTrait not stated //should-do//
- `ZubZet\Framework\Authentication\Permission\Role`: Role object over `z_role` rows with `is_group = 0`, extending `Authen: missing Backing table z_role and is_group = 0 filter not stated //should-do//
- `ZubZet\Framework\Authentication\Organization`: Organization object over `z_organization` without the `Permission` trait: missing Backing table z_organization and having no permissions of its own not stated //should-do//
- `ZubZet\Framework\Authentication\Permission\Permission`: Trait adding `permissionsAdd`, `permissionsRemove`, `hasAccessA: missing Trait name, availability on Group and buildPermissionVariants not documented //important//
- Effective permissions: A user's permissions are the union of their `z_user_permission` rows and the `z_role_permission` : missing Group permissions, active-only rule and source tables not stated //important//
- Organization access: Organizations carry no permissions; access comes through the linked group that members receive auto: missing Not stated that organizations hold no permissions; access only via linked group //should-do//
- Removal methods: `remove()` on `User`, `Role`, `Group` and `Organization` plus `permissionsRemove`, `rolesRemove` and `g: missing permissionsRemove/rolesRemove/groupsRemove soft removal (active=0) not stated //should-do//
- Retrievers: `byId`, `byIds`, `all`, `byName`, `byEmail`, `byUser` and `byRole` return only `active = 1` rows and only ac: missing Active-only results stated only for organizations, not User/Role retrievers //should-do//
- `id()`: Returns the integer id and throws once the instance is removed.: missing Throws after removal and always returns int; docs show int|string|null //nice-to-have//
- `clearFields()`: Empties the data array so every getter throws until `refresh()` or `loadObject()` is called.: missing clearFields() empties data so getters throw until refresh() not stated //should-do//
- Behaviour: Clears the fields and reloads the row via `byId()`; throws `RuntimeException("Object no longer exists")` when: missing RuntimeException when row is gone or inactive not stated //should-do//
- Role: Public reload method on each class that replaces the data and resets that class's cache keys.: missing Cache-key reset not stated; example wrongly calls loadObject on new User() //nice-to-have//
- `all(): static[]`: Returns every active object of that class.: missing Only active objects returned not stated //important//
- `byId(int|string $id): ?static`: Returns the active object or `null` when missing, inactive or outside the class filter.: missing Null for inactive or other-class (group vs role) ids not stated for User/Role //important//
- `byIds(int ...$ids): static[]`: Returns the active objects among the given ids and silently skips the rest.: missing Silent skipping of missing or inactive ids not stated //should-do//
- `permissionsAdd(string ...$permissionNames): void`: Inserts one active row per name into `z_user_permission` or `z_role_: missing One row per name in z_user_permission/z_role_permission and cache reset not stated //important//
- `permissionsRemove(string ...$permissionNames): void`: Sets `active = 0` on all rows of that object matching the names.: missing Soft removal (active = 0) of all matching rows not stated //important//
- `hasAccessAll(string ...$permissionNames): bool`: True only if every given name is covered by an exact match or wildcard: missing Wildcard-variant matching in hasAccessAll not stated //important//
- `hasAccessAnyOf(string ...$permissionNames): bool`: True if at least one given name is covered by an exact match or wild: missing Wildcard-variant matching in hasAccessAnyOf not stated //important//
- `Role::add(string $rolename): self`: Inserts an active `z_role` row with `is_group` taken from `$dbExpression` (0 for `R: missing Returned object and is_group taken from $dbExpression (Group::add) not stated //important//
- `Role::byName(string $name): ?Role`: Returns the single active role (or group on `Group`) with that exact name, or `null: missing Docs omit active-only, exact-name semantics and Group::byName variant //important//
- `Role::byUser(User $user): Role[]`: Returns the active roles (or groups on `Group`) the user is assigned to.: missing No 'active' filter; Group::byUser behaviour not documented //important//
- `Role::byAccessToAll(string ...$permissionNames): Role[]`: Returns roles whose permissions cover every given name, each : missing Wildcard-variant matching per name not stated //important//
- `Role::byAccessToAnyOf(string ...$permissionNames): Role[]`: Returns roles covering at least one given name through its : missing Wildcard-variant matching per name not stated //important//
- `update(string $newName): void`: Renames the role, then clears all fields so the object must be refreshed before further: missing Clears all fields; refresh needed before further reads not stated //important//
- `remove(): void`: Soft-deletes the role, flags permissions as changed and nulls the instance so later calls throw.: missing Instance nulled so later calls throw; permission-changed flag not stated //important//
- `getUsers(): User[]`: Returns active users assigned to the role, lazily loaded and cached in the `users` field until `re: missing Active-only users, lazy loading and caching until refresh not stated //important//
- `getPermissions(): string[]`: Returns permission names as a flat list from `z_role_permission` without deduplication, ca: missing Flat string list, no deduplication, caching in permissions field not stated //important//
- `User::rolesAdd(Role ...$roles): void`: Inserts `z_user_role` rows, flags permissions as changed and clears the user's f: missing Clearing user's fields (refresh needed) and permission-changed flag not stated //important//
- `User::rolesRemove(Role ...$roles): void`: Sets `active = 0` on the user's matching `z_user_role` rows.: missing Soft removal (active = 0) of z_user_role rows not stated //important//
- `User::getRoles(): Role[]`: Returns only non-group roles, lazily loaded and cached in the `roles` field.: missing Only non-group roles and lazy caching not stated //important//
- `User::byRole(Role $role): User[]` and `User::byGroup(Group $group): User[]`: Return active users with an active assignm: missing byGroup not documented; active-only assignment not stated //important//
- `User::getPermissions()`: Returns all effective permissions including those from roles and groups, lazily cached in the : missing Group-derived permissions and lazy caching not stated //important//
- `User::byAccessToAll(string ...$permissionNames): User[]`: Returns active users where each name is covered by a direct p: missing Group sources, wildcard matching and mixed sources per name not stated //important//
- `User::byAccessToAnyOf(string ...$permissionNames): User[]`: Returns active users covered for at least one name by a dir: missing Group sources and wildcard matching not stated //important//
- `Organization::add(?string $name, bool $createGroup = false, ?string $groupName = null): Organization`: Inserts an organ: missing Documented signature lacks third parameter ?string $groupName //should-do//
- Default group name: With `$createGroup` true and `$groupName` null the group is named `<name>_Group`.: missing $groupName parameter missing from signature; only {name}_Group stated //should-do//
- Without group: Without `$createGroup` the `groupId` column stays NULL and no group is created.: missing groupId stays NULL when $createGroup is false not stated //nice-to-have//
- `updateName(string $name): void`: Updates the active row and the cached `name` field, leaving the object usable.: missing Object stays usable (name cache updated, fields kept) not stated //should-do//
- `getGroup(): ?Group`: Returns the linked group or `null` when `groupId` is NULL or the group is inactive or not a group.: missing Null for inactive or non-group linked group not stated //should-do//
- `remove(): void`: Sets the organization `active = 0` but neither removes its group nor unassigns users nor nulls the ins: missing Linked group kept and instance not nulled not stated //should-do//
- `User::updateOrganization(?Organization $organization): void`: Sets or clears `z_user.organizationId` and clears the use: missing Clearing the user's fields afterwards (refresh needed) not stated //should-do//
- Not found: Lookups return `null` (`byId`, `byName`, `byEmail`, `Organization::byUser`) or an empty array (`byIds`, `byNa: missing Empty-array results of byIds/byAccessTo* not stated; null cases only via signatures //should-do//

### `docs/core-features/asset-proxy.md` (29)
- `league/mime-type-detection` `^1.16`: MIME detection of served assets.: missing league/mime-type-detection named; version ^1.16 not stated //nice-to-have//
- `components/jquery` `3.5.*`, `components/bootstrap` `4.6.*`, `components/font-awesome` `6.5.*`: frontend libraries mount: missing components/* packages named; pinned versions 3.5.*, 4.6.*, 6.5.* not stated //should-do//
- Needed at runtime: only `src/` and `web/` are needed; `web/Z.js` is served through the asset proxy as `/_zubzet/asset-pr: missing Z.js proxy path given; not stated that only src/ and web/ are needed at runtime //nice-to-have//
- `web/Z.js`: the only file in the package `web/` directory, registered as an asset-proxy source through `z_frontend_root`: missing Z.js served via proxy; web/ directory and z_frontend_root not mentioned //should-do//
- `$assetProxy` [internal]: the `AssetProxy` instance created during boot, typed and uninitialised until then.: missing zubzet()->assetProxy usage shown; typed-and-uninitialised-until-boot detail missing //optional//
- Asset proxy: `new AssetProxy` registers the bundled `IncludedComponents/assets/`, `z_frontend_root` and the bundled pack: missing Bundled sources registered at boot; IncludedComponents/assets source not listed //should-do//
- Handler: closure route inside group `/_zubzet` that calls `zubzet()->assetProxy->serve($args['assetPath'])`.: missing Closure route in /_zubzet group calling assetProxy->serve() not described //nice-to-have//
- `generateResourceLink` and `echo`: helper closures added after the view and layout files are included.: missing echo helper and the inclusion timing after view/layout files not stated //should-do//
- `$opt["generateResourceLink"]($url, $root = true)`: echoes the root folder, `$url` and a `?v=` cache-busting query, and : missing Prints instead of returning; ?v= cache-busting query and $root parameter missing //important//
- Scripts: jQuery, Popper, Bootstrap JS, `bs-custom-file-input` and `Z.js`, all through `generateResourceLink` on `_zubzet: missing Popper and bs-custom-file-input missing; not tied to layout_essentials_head //should-do//
- Styles: `bootstrap.min.css` plus the Font Awesome files `all`, `brands`, `v4-shims` and `fontawesome` under `css/font-aw: missing Font Awesome brands, v4-shims and fontawesome CSS files not listed //should-do//
- Served URL: Z.js is exposed as `_zubzet/asset-proxy/Z.js` because the framework `web/` directory (`z_frontend_root`) is : missing URL listed, but web/ (z_frontend_root) mount source never mentioned //should-do//
- Stylesheets: `css/bootstrap.min.css` and the Font Awesome files `all.min.css`, `brands.min.css`, `v4-shims.min.css` and : missing brands, v4-shims and fontawesome.min.css not listed; Font Awesome 4 class-name compatibility not stated //should-do//
- jQuery: Composer package `components/jquery` constrained to `3.5.*`, served at `js/jquery.min.js`.: missing Version constraint 3.5.* of components/jquery not stated //should-do//
- Bootstrap: Composer package `components/bootstrap` constrained to `4.6.*`, mounted at the proxy root as `css/bootstrap.m: missing Version constraint 4.6.* of components/bootstrap not stated //should-do//
- Font Awesome: Composer package `components/font-awesome` constrained to `6.5.*`, with styles under `css/font-awesome/` a: missing Version constraint 6.5.* and fonts under css/webfonts/ not stated //should-do//
- No CDN: All bundled frontend libraries are served locally by the asset proxy and no CDN reference exists.: missing popper and bs-custom-file-input not listed; explicit no-CDN statement missing //should-do//
- `$opt['generateResourceLink']($url, $root = true)`: Echoes `<rootFolder><url>?v=<assetVersion>`, and `$root = false` omi: missing Appended ?v=<assetVersion> query string and $root=false option not described //important//
- Echo, not return: The closure prints the link, so it is used inline inside a `src` or `href` attribute.: missing Examples use it inline, but never state that it echoes instead of returning //important//
- Purpose: Serves frontend assets from `src/`, `web/` and Composer packages through PHP so no public copy step is needed.: missing Sources src/ and web/ not named; only packages and no-copy //important//
- Route: `GET /_zubzet/asset-proxy/{assetPath:.+}` registered in the framework's `DefaultRoutes.php`, GET only and without: missing Route path stated; GET-only, DefaultRoutes.php registration and no access check not stated //important//
- Z.js: `z_frontend_root` (the package's `web/` directory) at the URL root, giving `Z.js`.: missing Z.js proxy path given; z_frontend_root (web/ directory) origin and precedence not stated //should-do//
- Application mounts: Mounts registered by the application come last and cannot override a path already provided by an ear: missing Registration order and first-match-wins stated; app mounts come last and cannot override not explicit //should-do//
- `$sourceRoot`: Should be an absolute path; a relative path resolves against the working directory.: missing Absolute path advised; relative path resolving against working directory not stated //should-do//
- `$urlPrefix`: Slashes are trimmed on both sides and the mount only matches paths that start with `<prefix>/`.: missing Optional prefix with example; slash trimming and '<prefix>/' matching rule not stated //should-do//
- Content type: `Content-Type` comes from `FinfoMimeTypeDetector::detectMimeTypeFromPath()` (file extension, case-insensit: missing Extension-based case-insensitive detection and application/javascript for Z.js not stated //should-do//
- Not found: HTTP 404 with the plain body "Asset not found: " plus the path escaped by `e()`.: missing 404 stated; body 'Asset not found: <escaped path>' not stated //nice-to-have//
- Escape rejection: A resolved path outside the mount root throws `\RuntimeException` "Invalid asset path: <resolved path>: missing Traversal 'rejected' stated; RuntimeException 'Invalid asset path' instead of 404 not stated //nice-to-have//
- Boundary check: It requires `<root>` plus a directory separator, so sibling directories sharing a name prefix are reject: missing Must stay within source directory; separator-boundary check for name-prefix siblings not stated //nice-to-have//

### `docs/core-features/configuration.md` (5)
- `z_config/z_settings.ini`: required settings file at the default `config_file` path, read without an existence check.: missing z_settings.ini location stated; not stated as mandatory, read without existence check //important//
- INI location: `z_config/z_settings.ini`, relative to the working directory, held in the built-in key `config_file`.: missing built-in `config_file` key and working-directory-relative path not stated //important//
- Availability: public on `ZubZet`, `Request` and `Response`, with the same semantics as the global `config()` helper.: missing availability on ZubZet instance not stated; only $res and $req shown //important//
- No key: an empty `$key` returns the complete settings array, including database and mail credentials.: missing empty-string key behaviour and that credentials are included not stated //important//
- `getModel()` and `getBooterSettings()`: mixed in via traits (see Controllers & Models and Configuration).: missing Usage documented, but not that Request and Response mix in both via traits //nice-to-have//

### `docs/core-features/console-commands.md` (15)
- Installed version lookup: `Composer\InstalledVersions::getPrettyVersion('zubzet/framework')` supplies the version `info:: missing info:startup shows version; InstalledVersions lookup and 'unknown' fallback not stated //nice-to-have//
- No dev tooling: `composer.json` has no `require-dev`, `scripts` or `bin`, so the CLI is only reachable as `php index.php: missing php index.php usage shown; no statement that composer.json has no bin/scripts/require-dev //important//
- `index.php`: project-root entry script that boots the framework for HTTP requests and for CLI use.: missing index.php named as CLI location; its role as HTTP and CLI boot script undocumented //important//
- `.coverage.session` and `.coverage/`: coverage session file and data directory created in the project root by the covera: missing Coverage start/stop documented; .coverage.session file and .coverage/ directory not named //nice-to-have//
- One script for web and CLI: `php index.php <command>` reaches the console through `ZubZet::execute()`.: missing php index.php <command> documented; same script serving web via ZubZet::execute() not stated //important//
- Startup banner: `info:startup` prints the value as `Environment`.: missing does not say the printed environment is the execution_type value //nice-to-have//
- Startup banner: `info:startup` prints it next to the framework version.: missing banner shows application name; the pageName key is not named //nice-to-have//
- Symfony built-ins: `php index.php` and `list` print the commands, `help <command>` shows usage, and the global options `: missing help/list/completion described; global options -h -q -v -V --ansi --no-ansi -n missing //important//
- Purpose: prints information for the startup process of the framework, shown after the dev environment starts.: missing Docs say post-deployment health check; 'after dev environment starts' not stated //important//
- Output content: a brand line with framework version and `pageName`, an `Open:` line with `host`, then rows `Environment`: missing Open: host line and config keys (pageName, execution_type, assetVersion) behind rows missing //should-do//
- Effect on requests: while the session file exists every web request and CLI call boots a coverage collector.: missing Requests measured is implied; session file booting collector on every web and CLI call missing //should-do//
- Option `--cli` (`-c`): prints a coloured text report to stdout instead of an HTML report.: missing Text instead of HTML stated; -c shortcut and coloured stdout output missing //important//
- Default report: an HTML report written to `.coverage/report/`, announced as `Report generated at <dir>`.: missing HTML report mentioned; output directory .coverage/report/ and announce line missing //should-do//
- Overview: a custom session-based collector measures coverage of the e2e project (and optionally the framework) while Cyp: missing phpunit/php-code-coverage 9, session-based collector, optional framework measurement not stated //nice-to-have//
- Content: `TextReport` prints the PHPUnit summary (`lowUpperBound` 50, `highLowerBound` 90, uncovered files shown) follow: missing thresholds 50/90, uncovered files shown and directory tree not described //optional//

### `docs/core-features/controllers-and-actions.md` (12)
- File and class: `app/Controllers/<Name>Controller.php` holds the global-namespace class `<Name>Controller`, found as `uc: missing Class naming shown; app/Controllers path and ucfirst(first segment) lookup not stated //important//
- Action methods: public `action_<name>` with the lower-cased URL segment, and `action_index` when no second segment exist: missing action_index default and action_<name> shown; lower-casing of URL segment not stated //important//
- Base class: controllers may extend `z_controller`, an alias of `ZubZet\Framework\Core\Controller`.: missing extends z_controller shown; alias of Core\Controller not stated //important//
- `z_controller`: alias of `Core\Controller`, the base class of app controllers.: missing extends z_controller shown; alias of Core\Controller not stated //should-do//
- `Request`: alias of `Message\Request`, used to type-hint action arguments.: missing Request type-hint used in examples; alias of Message\Request not stated //should-do//
- `Response`: alias of `Message\Response`.: missing Response type-hint used in examples; alias of Message\Response not stated //should-do//
- `action_fallback`: called when `action_<name>` does not exist; if absent too, the 404 page is shown.: missing 404 page shown when action_fallback is absent not stated //important//
- Signature: `public function action_<name>(Request $req, Response $res, ...$staticArguments)`, reached by URL as `/<contr: missing Cited page omits ...$staticArguments; only routing.md shows static args //important//
- `action_fallback`: catch-all for unknown actions of that controller, also used when an explicit route or middleware meth: missing Also used when an explicit route or middleware method is missing not stated //important//
- Fallback actions: a controller declaring `action_fallback` accepts any action name because `fallback` in its action list: missing Fallback documented for HTTP; run accepting any action name via action_fallback not stated //should-do//
- Action naming: Actions are `action_*` methods and hyphens in the URL become underscores, so `/z/edit-user` equals `/z/ed: missing Hyphen-to-underscore conversion in action names not documented //should-do//
- Default action: `/z` and `/z/index` both call `action_index`.: missing Generic default action only; /z and /z/index behaviour not stated //should-do//

### `docs/core-features/debug-bar.md` (25)
- `php-debugbar/php-debugbar` `^1.23.6`: debug bar used by `DebugBarBridge`.: missing PHP Debug Bar described; version ^1.23.6 not stated //nice-to-have//
- `debugbar_hide_internal_queries`: default `true`; hides queries issued by models using `IsInternalModel` (bundled `z_*` : missing which bundled z_* models are internal (all but z_organizationModel) not listed //should-do//
- Internal model marker [internal]: trait `IsInternalModel` (`public bool $isInternalModel = true`) marks bundled `z_*` mo: missing Bundled z_* models using it (except z_organizationModel) not stated //optional//
- Side effects: every render logs a `RENDER` entry on the `zubzet` logger (silently skipped if logging throws) and adds a : missing RENDER log entry on zubzet logger and silent skip on logging failure not stated //should-do//
- `layout_essentials_head` and `layout_essentials_body`: closures that print the framework essentials inside a layout.: missing layout_essentials_head closure and contents of the essentials not described //important//
- Output: prints the login token watcher and the debug bar markup.: missing Login token watcher script not described; only debug bar body covered //should-do//
- Omitting them: a custom layout that skips these two calls loses jQuery, Bootstrap, Font Awesome, `Z.js`, the token watch: missing Only debug bar loss stated; jQuery, Bootstrap, Z.js, token watcher not mentioned //should-do//
- Success side effects: Sets `$insertId` and `$result` (`get_result()`), closes the statement, refreshes `$lastHeartbeat`,: missing Heartbeat refresh, statement close and SLOW_QUERY logging side effects not documented //should-do//
- Affected rows: There is no accessor; only the debug bar receives them, otherwise read `getDatabaseConnection()->affected: missing No affected-rows accessor; getDatabaseConnection()->affected_rows alternative not stated //should-do//
- Bundled users of the trait: `z_fileModel`, `z_adminDashboardModel`, `z_userModel`, `z_permissionModel`, `z_loggerModel`,: missing Bundled models using the trait and z_organizationModel exception not listed //optional//
- Role: Prints the token-expiry watcher for logged-in users and `DebugBarBridge::renderBody()`.: missing Token-expiry watcher for logged-in users not mentioned; only body hook for debug bar //should-do//
- Call site: `extra.file`, `extra.line`, `extra.class` and `extra.function` come from Monolog's `IntrospectionProcessor` (: missing IntrospectionProcessor source (Monolog frames skipped) and removed callType not stated //nice-to-have//
- `extra.traceId`: The request trace id added by `BacktraceProcessor` to every record.: missing Not explained as per-request trace id added by BacktraceProcessor to every record //should-do//
- Standard collectors: PhpInfo, Messages, Request, Time, Memory and Exceptions from `php-debugbar` `^1.23.6`.: missing Collector names PhpInfo, Messages, Request, Time, Memory, Exceptions and ^1.23.6 not listed //nice-to-have//
- Rendering hook: `essentialsHead()` and `essentialsBody()` echo `renderHead()` and `renderBody()`, reached through `$opt[: missing Head hook and essentialsHead/essentialsBody echoing renderHead/renderBody not stated //should-do//
- Layout requirement: Layouts must call both hooks to show the bar; bundled `default_layout.php`, `min_layout.php` and `z_: missing Head essentials hook also required; mail_layout and empty layouts lack both //should-do//
- Collection trigger: Every successful `Connection::exec()` calls `collectQuery($sql, $durationSeconds, $rowCount, $values: missing Failed queries are never listed not stated //nice-to-have//
- Row count: `row_count` is `num_rows` for result sets and `affected_rows` otherwise, and `$values` are the bound values w: missing row_count semantics (num_rows vs affected_rows) and values without type string not stated //nice-to-have//
- Entry fields: `sql`, `duration` (seconds), `duration_str`, `row_count`, `is_success` (always `true`) and `params`.: missing Entry field names and is_success always true not listed //nice-to-have//
- Placeholder interpolation: `?` placeholders are replaced by single-quoted `addslashes()`d values (`NULL` for null) for d: missing addslashes() escaping, NULL for null and display-only caveat not stated //nice-to-have//
- Entry fields: `name` (`"<view> (layout: <layout>)"`), `param_count`, `params` (formatted with the DataFormatter), `type`: missing param_count, type, layout and start fields not listed //optional//
- Base: Extends php-debugbar's Monolog bridge handler (level `DEBUG`, bubbling) and reuses its `MessagesWidget` including : missing Extends php-debugbar Monolog bridge handler (level DEBUG, bubbling) not stated //nice-to-have//
- Record coverage: It receives records below `logger_level` that the database or stream handler drops, but nothing when `l: missing Records below logger_level shown; nothing shown when logger_enabled is false //nice-to-have//
- Headline: `"<traceId> [<channel> <LEVEL>]: <message>"`, escaped with `e()`, with the lowercase level name as label.: missing Exact headline format, escaping and lowercase level label not stated //optional//
- Searchable text: `traceId channel LEVEL message` followed by `key=value` pairs.: missing Search text format and key=value pairs not stated //optional//

### `docs/core-features/error-handling.md` (18)
- Required first settings: the INI must define `host`, `rootDirectory` and `showErrors` or boot fails (see Configuration).: missing showErrors documented; host/rootDirectory undocumented, no 'mandatory or boot fails' statement //urgent//
- `filp/whoops` `^2.18.4`: pretty error page used by `WhoopsHandler`.: missing Whoops described; version ^2.18.4 not stated //nice-to-have//
- Key prefix: `AutomatedSettings::set('host_working_directory', ...)` stores the key `automated_host_working_directory`; r: missing `automated_` key prefix rule and AutomatedSettings::set() not described //should-do//
- Internal API [internal]: the `AutomatedSettings` class is marked `@internal`, and `info:startup --pwd` is its only write: missing info:startup --pwd named, but not as only writer or @internal class //optional//
- Definition: required, no default; value `0`, `1` or `2` (class `BehaviorOption`), applied by `setExceptionBehavior()` at: missing required/no default and boot-time application not stated //important//
- `1` (`EXCEPTIONS`): `display_errors` on and `error_reporting(E_ALL)`; PHP errors are logged and left to PHP's handler; a: missing display_errors on, E_ALL, PHP errors logged, action exceptions rethrown not stated //important//
- `2` (`ALL`): every reportable PHP error is logged and turned into an `ErrorException`; `display_errors` and `error_repor: missing ErrorException, logging of each error, display_errors/error_reporting untouched not stated //important//
- Default: `vscode`; URL scheme used by Whoops open-in-editor links (`<editor>://file/<host path><relative file>:<line>`).: missing Default vscode stated; URL template <editor>://file/... not given //nice-to-have//
- Requires: `execution_type` `test` plus `automated_host_working_directory`.: missing Docs never say links require automated_host_working_directory to be set //nice-to-have//
- Absent: no editor links are configured.: missing behaviour when the setting is absent (no editor links) not stated //optional//
- Persistence: a string value is stored as `automated_host_working_directory` in `z_config/z_automated_setting.ini` throug: missing Setting key named; file z_config/z_automated_setting.ini and AutomatedSettings::set() storage missing //important//
- Rendering: Afterwards Whoops renders the exception (test mode), otherwise the throwable is rethrown to PHP's default fat: missing Non-test: throwable rethrown to PHP's default fatal display not stated //should-do//
- `BehaviorOption`: Constants `NONE` = 0, `EXCEPTIONS` = 1 and `ALL` = 2 with `BehaviorOption::isValidOption(int $option):: missing BehaviorOption::isValidOption(int): bool not mentioned //important//
- Promotion: Logs then throws `ErrorException($message, 0, $severity, $file, $line)` for every error passing `error_report: missing Logs first; only errors passing error_reporting(); ErrorException(message, 0, severity, file, line) //should-do//
- Handler: The error handler logs and returns `false` so PHP's default handling continues.: missing Error handler logs then returns false so PHP default handling continues //should-do//
- Non-test environments: Uncaught exceptions are rethrown to PHP's default handler and no stack trace page is rendered.: missing Uncaught exceptions rethrown to PHP's default handler not stated //should-do//
- URL format: `{development_editor}://file/{hostAppPath}{relative}:{line}` where the editor defaults to `vscode`.: missing URL format {editor}://file/{hostPath}{relative}:{line} not stated //nice-to-have//
- Pattern list: Hides keys of `_GET`, `_POST`, `_COOKIE`, `_SESSION`, `_SERVER` and `_ENV` whose lowercase name contains `: missing Patterns access, private and bearer not listed; list is elided //should-do//

### `docs/core-features/global-helper-functions.md` (15)
- Missing key: returns `$default` when `$useDefault` is truthy, otherwise throws `InvalidArgumentException` with `The sett: missing InvalidArgumentException and its message when $useDefault falsy not documented //important//
- Positional pitfall: the second positional argument is `$useDefault`, so pass defaults by name (`default: 5`).: missing no advice to pass the default by name (default: 5) //urgent//
- Current objects: `zubzet()->req` and `zubzet()->res` hold the current `Request` and `Response`, which are what `request(: missing request()/response() documented; zubzet()->req and ->res properties not mentioned //should-do//
- `$options` as array: only the key `layout` is read and any other key is ignored.: missing Only 'layout' key is read, other keys ignored not stated //important//
- `zubzet()`: returns `ZubZet::$instance`, or throws `NotInstantiatedException` (`ZubZet (The framework itself)`) before t: missing NotInstantiatedException before the framework exists not mentioned //important//
- `model($model, $dir = null)`: proxy to `zubzet()->getModel()` returning the cached model instance; resolution rules are : missing `$dir` parameter and cached-instance behaviour not documented //important//
- `request()`: returns `zubzet()->req`, or throws `NotInstantiatedException` (`Request`) when it is not a `Request`.: missing NotInstantiatedException when no Request is set up not mentioned //important//
- `response()`: returns `zubzet()->res` without an instance check.: missing does not say it returns the instance without any instance check //important//
- Named default: write `config('key', default: 5)`, since the second positional parameter is `$useDefault`.: missing named-argument `default:` usage not shown; signature wrongly gives $useDefault=false //urgent//
- `user()`: returns `zubzet()->user`, the request-scoped `ZubZet\Framework\Authentication\User`; check `isLoggedIn` to det: missing request-scoped User for anonymous visitors and isLoggedIn check not mentioned //important//
- Only `default` supported: any other name throws `InvalidArgumentException` (`Only the default connection is supported so: missing implies named connections; non-default name throwing InvalidArgumentException not stated //should-do//
- `view(string $document, array $opt = [], array|string $options = [])`: shortcut for `response()->render()` with the same: missing returned result and string layout form of $options not mentioned //important//
- Access points: `db()`, public `zubzet()->z_db` and protected `$this->z_db` inside models all return the one shared `Conn: missing zubzet()->z_db access and that all return one shared Connection not stated //important//
- Single logical connection: `db("other")` throws `InvalidArgumentException` "Only the default connection is supported so : missing Docs imply named connections; non-default name throwing InvalidArgumentException not stated //should-do//
- Access points: `user()`, `$req->getRequestingUser()`, `$req->booter->user`, `$opt["user"]` inside views and the global a: missing Lacks $req->booter->user and the global alias User; $opt["user"] only on other pages //important//

### `docs/core-features/layouts.md` (13)
- `app/Views/layout/`: layouts live here, matching the bundled `layout/default_layout.php`, `layout/min_layout.php`, `layo: missing layout/default_layout.php location stated; min/empty/mail layouts not named //should-do//
- Layout files: layouts sit in `app/Views/layout/` as `<name>_layout.php`, and the default layout is `layout/default_layou: missing layout/default_layout.php location stated; <name>_layout.php naming not stated //should-do//
- One shared `Response`: a single instance is handed to controller constructors, route middleware and actions, so its defa: missing Default-layout scopes documented; single shared Response instance not stated //should-do//
- Typical uses: a CLI gate with `checkPermission("console")` or `setDefaultLayout()` for all actions of the controller.: missing CLI gate via checkPermission('console') not mentioned; constructor only shown for default layout //should-do//
- `$options` as plain string [deprecated]: legacy shorthand for `["layout" => $string]` that bundled controllers still use: missing String form not marked deprecated; array form ['layout' => ...] undocumented //nice-to-have//
- `setDefaultLayout(string $layout)`: clears the instance stack and installs `$layout` as its only entry.: missing Clearing of instance stack and 'only entry' semantics not stated //important//
- `popDefaultLayout()`: removes and returns the top entry so the previous default applies again.: missing Returning the removed top entry not stated //should-do//
- `Response::setGlobalDefaultLayout(string $layout)`: clears the global stack and installs `$layout` as its only entry.: missing Clearing of global stack and 'only entry' semantics not stated //should-do//
- `Response::pushGlobalDefaultLayout(string $layout)`: pushes a layout on top of the global stack.: missing Method name pushGlobalDefaultLayout only implied by 'global equivalents' //should-do//
- `Response::popGlobalDefaultLayout()`: removes and returns the top global entry.: missing Method name popGlobalDefaultLayout and returned entry not stated //should-do//
- LIFO nesting: push and pop nest in LIFO order and every push must be matched by exactly one pop.: missing LIFO order and one-pop-per-push requirement not stated //should-do//
- `resolveDefaultLayout()`: protected method computing the effective default layout, instance stack first.: missing Protected resolveDefaultLayout() not named; only resolution order described //nice-to-have//
- Where to set a default: a controller `__construct(Request $req, Response $res)`, a route `middleware` callback or action: missing Action code and constructor for instance scope not stated; scopes paired differently //should-do//

### `docs/core-features/logging.md` (26)
- `monolog/monolog` `^2.11`: logging backend used by the `Logger` classes.: missing Monolog named as backend; version ^2.11 not stated //should-do//
- Falsy values: `false`, `off`, `no`, `0` and empty in the INI; an env value `false` stays truthy.: missing other falsy INI values and env `false` staying truthy not stated //should-do//
- Default: `database`; selects the log backend, matched case-sensitively.: missing case-sensitive matching of the type value not stated //important//
- `database`: writes through `DatabaseLogger` and the `z_logger` model.: missing Table z_interaction_log documented; DatabaseLogger and z_logger model not named //should-do//
- `stream`: writes JSON lines through `StreamLogger` to `logger_stream_url`.: missing JSON lines to logger_stream_url stated; StreamLogger class not named //should-do//
- `logger_stream_url`: default `php://stderr`; only read for the `stream` type, a stream or file path (relative paths reso: missing Relative paths resolving against working directory not stated //should-do//
- Read once per channel: logger settings are applied when a channel's logger is first created in a request, later changes : missing lazy creation noted; settings applied only at first channel creation not stated //nice-to-have//
- `text`, `value`: `MEDIUMTEXT DEFAULT NULL`; `text` is the log message and `value` the JSON-encoded record.: missing MEDIUMTEXT stated; DEFAULT NULL not stated //extra-effort//
- `created`: `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()`.: missing NOT NULL DEFAULT CURRENT_TIMESTAMP() not stated, only TIMESTAMP //extra-effort//
- Monolog base: `ZubZet\Framework\Logger\Logger` extends `Monolog\Logger` (`monolog/monolog` `^2.11`), so PSR-3 level meth: missing ZubZet Logger extends Monolog\Logger and monolog ^2.11 constraint not stated //important//
- Level methods: `debug()`, `info()`, `notice()`, `warning()`, `error()`, `critical()`, `alert()`, `emergency()` and `log(: missing log($level, $message, $context) method not listed //important//
- `Logger::APP` (`"app"`): Default channel used by `logger()` without argument.: missing Logger::APP constant not documented; only default 'app' channel //important//
- `Logger::ZUBZET` (`"zubzet"`): Channel on which the framework emits its own events.: missing Framework events using the 'zubzet' channel (Logger::ZUBZET) not stated //should-do//
- Caching: Channels are cached per request in `StaticCache` under type key `logger`, each with its own handler and process: missing StaticCache key 'logger' and per-channel handler/processor instances not stated //nice-to-have//
- Registered loggers: They bypass the `logger_*` handler and processor setup, so they get no traceId, backtrace `extra` or: missing Registered loggers getting no traceId, backtrace extra or debug bar collector not stated //nice-to-have//
- Disabled logging: With `logger_enabled` falsy only a `NullHandler` is pushed and the function returns early, so no proce: missing NullHandler stated; early return and no processors running not stated //important//
- Normal setup: The handler for `logger_type` (default `database`) gets `setLevel(logger_level)` and a `BacktraceProcessor: missing BacktraceProcessor pushed with same level; handler setLevel(logger_level) not stated //important//
- Filtering: Handlers drop records below `logger_level`, so with the default `info`-level framework events (`RENDER`, logi: missing Default notice level means info events (RENDER, login/logout) are not stored //urgent//
- `stream` handler (`ZubZet\Framework\Logger\Method\StreamLogger`): Extends Monolog `StreamHandler` writing to `logger_str: missing Extends Monolog StreamHandler; constructor forwards extra args (level, bubble, permission, locking) //important//
- Stream format: One JSON object per line, formatted by `ZubZet\Framework\Logger\JsonFormatter`, with empty `context` and : missing JsonFormatter class and empty context/extra written as {} not stated //should-do//
- Stream environment: `JsonFormatter::format` calls `model("z_logger")->appendEnvironment()`, which needs `request()`, so : missing Needs request(); throws NotInstantiatedException before the Request exists //nice-to-have//
- Stream target: Monolog creates missing log directories and applies no rotation, and the `php://stderr` default sends log: missing Missing directories created and no rotation not stated //should-do//
- `message` and `context`: `message` equals the `LogEventType` string for framework events and `context` is the array pass: missing Framework events use LogEventType string as message; example shows prose message //should-do//
- `log(array $logRecord)`: Inserts one `z_interaction_log` row via `dbInsert` + `exec` after adding environment info and r: missing z_logger model, dbInsert and removed formatted key not mentioned //optional//
- Shared use: `appendEnvironment` is also called by the stream `JsonFormatter`, so both log methods carry the same `extra`: missing Shared appendEnvironment not named; keys land in extra, not an environment key //nice-to-have//
- Encoding fallback: On a `JsonException` the stored value becomes `{"message": ..., "encoding_error": ...}`, with message: missing Default message 'Log encoding failed' when no message exists not stated //nice-to-have//

### `docs/core-features/maintenance.md` (15)
- Definition: default `disabled`; accepts `disabled`, `soft`, `enabled` or `full` (case-insensitive) and falls back to `di: missing Default disabled not stated explicitly; only unknown-value fallback //important//
- Read per request: the mode is evaluated on every request by the gate, so editing it takes effect immediately; `CONFIG_MA: missing per-request evaluation and CONFIG_MAINTENANCE_MODE needing allow_env_config not stated //should-do//
- Bypass cookie: the admin panel button sets the cookie `maintenance` (value `true`) for one day on the `rootFolder` path,: missing cookie value `true` and rootFolder path not stated //important//
- CLI effects: without a matching FastRoute route a CLI run is handed to the Symfony Console application, and the maintena: missing CLI maintenance pass-through stated; FastRoute-miss handoff to Symfony Console not described //should-do//
- Status cards: Currently Active (`Maintenance` or `Normal` from `isActive()`), Current Mode (`getMode()`, escaped) and Yo: missing Three status cards (Currently Active, Current Mode, Your Browser Status) not named //nice-to-have//
- Bypass button: "Bypass Cookie" sends POST action `bypass-maintenance` through `Z.Request.action`, reloads on `result` `s: missing POST action bypass-maintenance, reload on success and failure alert text not described //should-do//
- Cookie written: Name `maintenance` (`MaintenanceHandler::$COOKIE_KEY`), value `true`, expiry now plus `TIMESPAN_DAY_1`, : missing Cookie value true, root-folder path and missing secure/httponly flags not stated //important//
- Timing: Runs on every request right after configuration is loaded and before the logger hooks, asset proxy, routing, RES: missing Routing and REST endpoints ordering not stated //should-do//
- Failure safety: Resolution problems fall through to `disabled`, so the gate can never lock the site by itself.: missing Only unknown values fall back; other resolution failures and no-lockout absent //should-do//
- Invalid mode: Unknown or empty values fall back to `disabled` without any warning or log.: missing Unknown values fall back to disabled; silent behaviour (no warning/log) and empty value not stated //important//
- Setting it: Admins set it from the admin page `z/maintenance` (24h, path = root folder, no `secure`/`httponly`/`samesite: missing Admin button and 24h expiry stated; cookie path and missing secure/httponly/samesite flags not //important//
- Ordering: The cookie cannot be set while the gate blocks the app, so it must be obtained before `soft` mode is enabled.: missing Page unreachable when blocked implied; instruction to obtain cookie before enabling soft not explicit //important//
- Status and headers: Status `503` with `Content-Type: text/html; charset=UTF-8` and `Retry-After: 300`; the body is the m: missing 503 and Retry-After: 300 stated; Content-Type text/html; charset=UTF-8 not stated //important//
- Body type: The body is always HTML regardless of request type, so JSON, REST, API and asset-proxy requests receive the H: missing HTML template for HTTP stated; JSON/REST/API/asset requests also receive HTML not stated //should-do//
- Switching modes: The only switch is the `maintenance_mode` setting (z_settings.ini or `CONFIG_MAINTENANCE_MODE` env); no: missing Settings file or env var stated; CONFIG_MAINTENANCE_MODE name and no CLI/admin toggle not stated //important//

### `docs/core-features/migrations/examples.md` (2)
- Deferred generation: The table API only records actions; SQL is generated afterwards in call order and flattened into on: missing Combined example shows call order; table API only records, SQL generated afterwards missing //should-do//
- Custom `timestamp` type: `ZubZet\Framework\Database\Migration\Type\TimeStamp` declares `TIMESTAMP` on MySQL and PostgreS: missing Type row maps to TIMESTAMP; TimeStamp class and DATETIME fallback on other platforms missing //should-do//

### `docs/core-features/migrations/index.md` (82)
- `doctrine/dbal` `^3.10 || ^4.4`: schema introspection and SQL generation for PHP migrations and the migration model.: missing Doctrine DBAL named for schema builder; version range not stated //should-do//
- `app/Database/seed/`: seed files read by `db:seed`, with sub-folders usable as selectors, created with mode 0755 when mi: missing Seed folder and path selectors documented; auto-creation with mode 0755 not stated //important//
- Migration file names: `YYYY-MM-DD[_<integer version>]_<Name>.sql` or `.php`, with a year of 2000 or later and a non-empt: missing Filename format stated; year 2000+ and non-empty name constraints not stated //important//
- Framework prefix `z_`: marks framework-owned names such as `z_config/`, `z_settings.ini`, `z_automated_setting.ini`, `z_: missing z_migration_lock and z_version named; z_ prefix convention itself not stated //should-do//
- Bookkeeping tables: `db:migrate`, `db:sync` and `db:unlock-migration` first create `z_migration_lock` and `z_version` wh: missing Tables auto-created stated; which commands (migrate, sync, unlock-migration) create them missing //should-do//
- Invalid file names: a migration file name not matching `YYYY-MM-DD[_<version>]_<Name>` raises `InvalidArgumentException`: missing Error and abort stated; InvalidArgumentException raised before command's own error handling missing //should-do//
- Environment filter source: `.sql` migrations always have environment `default` and cannot be marked skip or manual.: missing Settings shown for PHP only; SQL files always environment default, no skip/manual not stated //should-do//
- Option `--environments-excluded` (`-e`): repeatable, value-required environment names whose migrations are skipped with : missing Option documented; repeatability for db:migrate and skip message text missing //important//
- Definition: runs without executing SQL, locking or recording `z_version` rows.: missing Says no changes committed; no SQL, no lock, no z_version rows not spelled out //important//
- Effect: when enabled, skipped migrations only produce a warning, and when disabled they abort the run with exit 1.: missing Warning-only vs abort exit 1 not stated; Safety section wrongly says default aborts //important//
- Lock check: when `z_migration_lock` holds a locked row it prints `Migrations are currently locked. Aborting import.` plu: missing Lock explained; abort message, exit code 1 and hint text not stated //important//
- File sources and order: files come from `./app/Database/migrations` (recursive, `.sql` and `.php`) plus framework migrat: missing Location and date/index order given; recursive scan and name as final sort key missing //important//
- Skipped detection: files not in `z_version` whose date is not after the last executed migration are warned about as `War: missing Skipped check mentioned; detection rule and 'Warning: ... were skipped' output format missing //important//
- Abort on skipped: with `--force` disabled it prints `Aborting import due to skipped migrations. Use --force to ignore.` : missing Abort on skipped stated; exact message and exit code 1 missing //important//
- Locking: outside dry mode it inserts an `is_locked` row into `z_migration_lock` before executing.: missing Lock table described; is_locked row inserted only outside dry mode not stated //should-do//
- Skip flag: a migration marked skip prints `Skipping migration (marked to skip): <file>` and is recorded as executed outs: missing Marked executed documented; 'Skipping migration (marked to skip)' message and no recording in dry missing //important//
- Manual flag: a migration marked manual prints `Migration requires manual execution: <file>` and returns 0 at once, leavi: missing Stops and leaves lock documented; exact message and exit code 0 missing //important//
- Bookkeeping: after success `z_version` gets a row with `migration_name` (basename), `migration_date`, `migration_version: missing Only name documented; migration_date, migration_version and sha1 file_hash missing //should-do//
- SQL failure: prints `Error importing <file>: <message>` and exits 1 without releasing the lock, and earlier migrations s: missing Left-over lock after crash noted; error message, exit 1, earlier migrations staying applied missing //urgent//
- Purpose: shows the current migration status, described as `Show the current migration status`, and takes no arguments or: missing Lock-state check documented; takes no args/options missing; console page wrongly says it lists migrations //important//
- Exit status: inverted, 0 (`Command::SUCCESS`) when the table is locked and 1 (`Command::FAILURE`) when it is unlocked.: missing 0/1 shown next to states but never called exit status or marked inverted //urgent//
- Option `--startVersion`: value-required lower version bound, only compared for files dated exactly on the start date and: missing Requires --start noted; compared only on exact start date missing //important//
- Option `--endVersion`: value-required upper version bound, only compared for files dated exactly on the end date and req: missing Requires --end noted; boundary-date-only comparison and 'from which' help text quirk missing //important//
- Options `--environments-included` (`-i`) and `--environments-excluded` (`-e`): repeatable environment filters with the s: missing Semantics and repeat shown; the Skipping migration messages not documented //important//
- Option `--dry` (`-d`): lists what would be synchronised without writing `z_version` rows or unlocking.: missing Dry listing documented; dry also skipping the unlock step not stated //important//
- Marking: every pending file passing the filters is recorded in `z_version` as executed without running its SQL, printing: missing Marking without SQL documented; 'Synchronizing migration: <file>' output line missing //important//
- Unlock side effect: outside dry mode it always removes the lock row and prints `Table was unlocked.`, which is the way o: missing Sync after manual migration noted; that sync always unlocks (non-dry) not stated //important//
- Version without date: `Cannot use start version without specifying a start date.` and `Cannot use end version without sp: missing Requires --start/--end noted; exact error messages and exit 1 missing //should-do//
- Bad date format: `Invalid start date format. Expected format: YYYY-MM-DD.` and the matching end message, with strict rou: missing Format given; error messages, exit 1 and strict round-trip rejecting 2005-1-1 missing //should-do//
- Option `--environments-included` (`-i`): repeatable path selectors, relative to `./app/Database/seed`, that are added ba: missing Repeatable not stated for seed --environments-included, only for excluded //important//
- Nested migrate: it runs `db:migrate` in-process with default options, shares the output, ignores its result, and prints : missing Migrate step listed; default options, shared output, ignored result and message missing //should-do//
- Starting set: all `.sql` and `.php` files below `./app/Database/seed` (recursive, sorted by path), with the original ord: missing All seed files noted; recursive discovery and path-sorted order missing //should-do//
- Selector match: a selector matches an exact file path or any file below that folder, and empty selectors are ignored.: missing Folder or exact file matching documented; empty selectors being ignored not stated //should-do//
- PHP seeds: `.php` files first flush the SQL buffer, then run immediately through `SeedPHP::loadPhpSeed()` (class named l: missing run() convention noted; SQL buffer flush first and execQuery missing; hyphen-to-underscore claim wrong //should-do//
- Purpose: unlocks the migration table if it is locked, described as `Unlock the migration table if it is locked.`, and ta: missing Purpose documented; takes no arguments missing; syntax wrongly names db:unlock-migrations //important//
- Output when locked: removes the lock row and prints `Migration table unlocked.`: missing Lock removal documented; 'Migration table unlocked.' output missing //should-do//
- `z_migration_lock`: Lock table with `id` (`INT AUTO_INCREMENT` primary key), `is_locked` (`BOOLEAN` default 0, 1 means l: missing Column names/types (is_locked default 0, locked_at) not given //extra-effort//
- `z_version`: One row per executed migration with `id`, `migration_name`, `migration_date`, `migration_version`, `file_ha: missing Columns migration_date, migration_version, file_hash, active, created not listed //extra-effort//
- File types: Only `.sql` and `.php` files (extension matched case-insensitively) are collected; other files are ignored s: missing Both types listed; case-insensitive extension and silently ignored other files missing //important//
- Date format: The first segment must match `^\d{4}-\d{2}-\d{2}$` and is parsed with `DateTime::createFromFormat('!Y-m-d'): missing 'Valid ISO date' only; exact regex and !Y-m-d parsing to 00:00:00 missing //important//
- Version segment: The second segment counts as version only if it passes `FILTER_VALIDATE_INT`; without one the version i: missing Optional numeric INDEX; FILTER_VALIDATE_INT rule and default version 0 missing //important//
- Execution order: Migrations run by date, then numeric version, then `strcmp` of the name.: missing Date then index order given; strcmp of name as final tie-breaker missing //important//
- Failure behaviour: Errors are `InvalidArgumentException`s thrown while sorting, before any lock is taken; they abort the: missing Error and abort noted; InvalidArgumentException before any lock, message on stderr missing //should-do//
- No flags: SQL files cannot set skip, manual or an environment and always run in environment `default`.: missing Settings shown only for PHP; SQL files cannot set them, always environment default //should-do//
- `tableAlter(string $name): Table`: Clones the existing table from `fromSchema` and unknown tables throw; the DBAL compar: missing Purpose documented; unknown table throws and no diff yields no SQL missing //important//
- `run(string $sql)`: Adds raw SQL at that position of the action order.: missing Raw SQL documented; queued at its call position in the action order not stated //should-do//
- Properties: Public `$skip` (false), `$manual` (false) and `$environment` (`"default"`) on the `Migration` base class.: missing Environment default documented; public $skip/$manual properties and false defaults missing //should-do//
- `skip()`: `db:migrate` records the migration as executed without running its SQL (`Skipping migration (marked to skip): : missing Marked executed documented; skip message and nothing recorded in --dry missing //important//
- `setManual(bool $manual)`: `db:migrate` stops at this migration with `Migration requires manual execution: <file>` and e: missing Stops and leaves lock documented; exact message and exit code 0 missing //important//
- Manual workflow: Run the SQL by hand, then `db:sync` (limit it with `--end` and `--endVersion`), because `db:migrate` le: missing Manual, sync, re-run flow noted; limiting sync with --end/--endVersion missing //important//
- Include list `-i`: When given, only `default` plus the listed environments run; others print `Skipping migration (not in: missing Default always included noted; 'only default plus listed run' and skip message missing //important//
- Exclude list `-e`: Listed environments print `Skipping migration (in excluded environments): <file>`; it is checked afte: missing Exclude documented; checked after -i, default can be excluded, skip message missing //important//
- `markAsExecuted()`: Writes one `z_version` row per migration after its SQL succeeded, with basename, `Y-m-d` date, versi: missing Only name documented; Y-m-d date, version and sha1 file hash missing //should-do//
- Bookkeeping tables: `db:migrate`, `db:sync` and `db:unlock-migration` create `z_migration_lock` and `z_version` on deman: missing Auto-creation stated; which commands create the tables and that db:status does not missing //should-do//
- Lock row: The lock is a `z_migration_lock` row with `is_locked` = 1; `lockMigrations()` inserts one and `unlockMigration: missing Lock table role documented; is_locked=1 row insert and delete mechanics missing //should-do//
- Locked abort: `db:migrate` aborts with `Migrations are currently locked. Aborting import.` (exit 1) plus a hint about pa: missing Lock prevents parallel runs; abort message, exit 1 and hint text missing //important//
- Stuck lock: The lock stays set after a SQL error (exit 1) or a manual-migration stop (exit 0).: missing Lock after crash or manual stop noted; staying set after SQL error (exit 1) missing //important//
- Clearing: Use `db:unlock-migration` or a non-dry `db:sync`; `db:sync` never checks the lock.: missing Unlock command documented; non-dry db:sync also clearing the lock, never checking it, missing //important//
- `db:unlock-migration`: Ensures the tables exist, prints `Migration table is not locked.` when nothing is locked, otherwi: missing Lock removal documented; table creation, both messages and exit 0 missing //important//
- `db:status`: Prints `Migration Lock Status: LOCKED|UNLOCKED` and its exit code is inverted (0 when locked, 1 when unlock: missing States with 0/1 shown; output line text and 'inverted exit code' not stated //urgent//
- File set: Application migrations plus bundled migrations (unless `--exclude-external`), sorted by date, version and name: missing Sources and --exclude-external documented; name as final sort key missing //important//
- Skipped detection: Files dated on or before the latest executed migration but not recorded are listed under `Warning: Th: missing Skipped check mentioned; rule 'on or before latest executed, unrecorded' and warning header missing //important//
- `--force=false`: Aborts with `Aborting import due to skipped migrations. Use --force to ignore.` (exit 1).: missing Abort on --force=false stated; exact message and exit 1 missing //important//
- Force parsing: Any value that `FILTER_VALIDATE_BOOLEAN` does not read as true (`false`, `0`, `no`, `off`, unknown text) : missing Falsy examples given; 0/no/off and unknown text also disabling force missing //should-do//
- `--dry`: Prints `Importing migration: <file>` per file without executing or recording.: missing Dry documented; 'Importing migration' per file and nothing recorded missing //important//
- SQL error: Prints `Error importing <file>: <message>`, returns 1 and leaves the lock set.: missing Left-over lock noted; error message, exit 1 and earlier migrations staying applied missing //important//
- `Cannot use start version without specifying a start date.`: `--startVersion` was given without `--start`.: missing Requires --start noted; exact error message and exit 1 missing //should-do//
- `Invalid start date format. Expected format: YYYY-MM-DD.`: `--start` is not a real date in that format.: missing Format given; error message, exit 1 and real-date requirement missing //should-do//
- `Cannot use end version without specifying an end date.`: `--endVersion` was given without `--end`.: missing Requires --end noted; exact error message and exit 1 missing //should-do//
- `Invalid end date format. Expected format: YYYY-MM-DD.`: `--end` is not a real date in that format.: missing Format given; error message, exit 1 and real-date requirement missing //should-do//
- Strict dates: Dates are parsed with `!Y-m-d` and the formatted result must equal the input, so overflow dates are reject: missing Format given; strict round-trip comparison rejecting overflow dates missing //should-do//
- Range rules: A migration is skipped when its date is before `--start` or after `--end`; on the boundary date `--startVer: missing Start/end documented; version bounds applying only on boundary date missing //important//
- Output: Prints `Synchronizing migration: <file>` per file; `--dry` skips recording and nothing pending prints `No pendin: missing Dry documented; output lines and 'No pending migrations to synchronized found.' continuing missing //should-do//
- Unlock: A non-dry run always ends by unlocking the migration table (`Table was unlocked.`).: missing Sync after manual migration noted; non-dry sync always unlocking not stated //should-do//
- Destructive by default: All data is lost unless `--skip-migrations`, and DROP and CREATE DATABASE rights are needed (see: missing Data loss and -s documented; DROP/CREATE DATABASE rights via elevated credentials missing //urgent//
- Nested migrate: `db:migrate` runs through the shared console application without options, so `-i` and `-e` are not forwa: missing Migrate step listed; no options forwarded (-i/-e) and ignored exit code missing //should-do//
- Seed files: `.sql` and `.php` files below `./app/Database/seed`, found recursively with no naming rule.: missing Both types and folder noted; recursive discovery and no naming rule missing //important//
- Selectors: `-i` and `-e` take paths relative to the seed root, either a folder or a file including its extension, with n: missing Relative folder or file selectors documented; no globbing not stated //important//
- `Seed::addQuery(Query $query)`: Queues any CakePHP query (from `dbUpdate()`, `dbDelete()` or `dbSelect()`) and the publi: missing addQuery documented; public $queries array missing; docs wrongly say dbInsert auto-registers //important//
- Query builder access: Seeds use the `CanBuildQuery` trait (`dbSelect`, `dbInsert`, `dbUpdate`, `dbDelete`, `getQueryBuil: missing CakePHP and getQueryBuilder noted; CanBuildQuery trait and dbSelect/dbUpdate/dbDelete missing //should-do//
- `ensureMigrationTablesExist(): void`: Creates `z_migration_lock` and `z_version` through DBAL when missing.: missing Tables auto-created noted; ensureMigrationTablesExist() and DBAL-based creation missing //optional//
- `cy.dbSeed()`: runs `db:seed`, which drops and recreates the test database, and both CI workflows run `db:seed` before t: missing CI workflows running db:seed before the suite not stated //nice-to-have//

### `docs/core-features/models.md` (17)
- File and class: `app/Models/<Name>Model.php` holds the global-namespace class `<Name>Model`, loaded by `model("<Name>")`: missing <Name>Model loaded via getModel('Name') shown; app/Models file path not stated //important//
- Base class: models extend `z_model`, an alias of `ZubZet\Framework\Core\Model`.: missing z_model inheritance stated; alias of Core\Model not stated //important//
- `z_model`: alias of `Core\Model`, the base class of app models.: missing z_model inheritance stated; alias of Core\Model not stated //should-do//
- Models: obtained with `$req->getModel()`, `$res->getModel()` or `model()`; the base controller has no `getModel()`.: missing $res->getModel() missing; base controller having no getModel() not stated //important//
- `ZubZet\Framework\Core\Model` (alias `z_model`): base class for app models; uses traits `CanBuildQuery` and `CanRetrieve: missing FQCN ZubZet\Framework\Core\Model, alias and traits not named //important//
- Availability: trait `CanRetrieveModel` is used by `ZubZet`, `Request` and `Response` (via `RequestResponseHandler`) and : missing Trait availability on ZubZet, Request, Response not stated; only $this->getModel() in models //should-do//
- `getInsertId()`: insert id of the last query, not changed by internal logging.: missing Unaffected by internal logging not stated //important//
- `resultToArray()`: all rows of the last result as associative arrays; an initial array can be passed through.: missing Optional initial array argument not mentioned //important//
- `resultToLine()`: next row of the last result as associative array, `null` when exhausted.: missing Next-row cursor behaviour on repeated calls and null when exhausted not stated //important//
- Escaping: None beyond parameter binding, the query text is used exactly as written.: missing Only warns to use placeholders; query text used verbatim, no escaping not stated //important//
- `getInsertId()`: Returns `$insertId`, which every `exec()` overwrites, SELECTs included.: missing Overwritten by every exec including SELECTs not stated //important//
- `resultToArray($out = []): array`: Reads all remaining rows with `fetch_assoc()` and appends them to `$out`; the cursor : missing Optional $out parameter and consumed cursor not stated //important//
- Row shape: Rows are associative arrays keyed by column name and numeric columns arrive as native PHP numbers (e2e expect: missing Numeric columns arriving as native PHP numbers not stated //should-do//
- `resultToLine(): ?array`: Returns the next row via `fetch_assoc()` or `null` when none is left, so it can drive a loop.: missing Returns next row or null when exhausted (loop use) not stated; docs say first row //important//
- `countResults()`: Returns `num_rows` of the last result and is only valid after a SELECT.: missing Only valid after a SELECT not stated //should-do//
- Chaining: It returns the `Connection`, so `resultToArray()`, `resultToLine()`, `mergeAsGroup()` or `getInsertId()` can f: missing Returned Connection for chaining only shown in examples, not stated //important//
- Wrapped methods: `getInsertId()`, `resultToArray()` (passes `$out`), `resultToLine()`, `getFullTable()`, `getTableWhere(: missing getFullTable, getTableWhere, countTableEntries and getResult wrappers not documented //should-do//

### `docs/core-features/parameter-abstraction.md` (16)
- Reading: further URL segments are read with `Request::getParameters()`, `getReadableParameter()` and `getUrlParts()`.: missing getReadableParameter() and getUrlParts() not mentioned; only getParameters() documented //important//
- Fallback offset: with `action_fallback` the action segment itself is a parameter, hence offset `-1` in `getParameters()`: missing getParameters(-1) example only; getReadableParameter offset and the reason not explained //should-do//
- `getGet(?string $key = null, mixed $default = null)`: returns the whole `GET` array without a key, else the value, or `$: missing No-key call returning whole GET array; default also applies when value is null //important//
- `getPost(?string $key = null, mixed $default = null)`: same contract for `POST`, with `<#decURI#>` prefixed values alrea: missing No-key call returning whole POST array; default also applies when value is null //important//
- `getFile(?string $key = null, mixed $default = null)`: returns the raw `$_FILES` entry (`name`, `type`, `tmp_name`, `err: missing Entry fields (name,type,tmp_name,error,size) and whole-array no-key return not stated //important//
- `getCookie(?string $key = null, mixed $default = null)`: same contract for cookies, and reflects removals made earlier i: missing No-key call returns all cookies; reflects earlier unsetCookie() removals not stated //important//
- `getFile` default: `getFile($key, $default)` returns `$default` when no file was uploaded under that key, like `getGet`,: missing getFile $default only implied by 'like getPost'; not stated explicitly //should-do//
- `$length = 1`: returns the single string, or `false` (not `null`) when absent.: missing Returns false (not null) when the parameter is absent //should-do//
- `$length = 1` with `$val`: returns a boolean from a loose `==` comparison.: missing Loose == comparison semantics not stated; only a false example shown //should-do//
- Other lengths: any other `$length` returns `array_slice($params, 0, $length)`, and `null` returns all remaining segments: missing Lengths other than 1 return array_slice of that length; not described //should-do//
- Behavior: pass-through to PHP `setcookie(...func_get_args())` without declared parameters, so only positional arguments : missing Pass-through via func_get_args(), no declared parameters, positional or options-array only //important//
- Behavior: removes the entry from `$req->input->COOKIE` and sends an expired cookie (empty value, expires `1`).: missing Removal from request COOKIE and empty value with expires=1 not stated //should-do//
- `<#decURI#>` prefix: POST values starting with it are recursively replaced by their `rawurldecode`d remainder (Z.js send: missing Recursive rawurldecode, Z.js origin of prefix, and $_POST itself being modified //should-do//
- `Response::unsetCookie(string $name, string $path = "/", string $domainScope = "")`: Removes the cookie from the request: missing Signature (name, path, domainScope) and expired-cookie (timestamp 1) behaviour not described //important//
- `<#decURI#>` prefix: Text values are sent as the prefix plus `encodeURIComponent(value)`.: missing Client side: text values sent as prefix plus encodeURIComponent(value) not stated //should-do//
- Server decoding: `Input\State::fromRequest` strips the prefix and applies `rawurldecode` to every POST item recursively,: missing Recursive rawurldecode over every POST item in Input\State::fromRequest not stated //should-do//

### `docs/core-features/password-handling.md` (19)
- Argon2 support: new password hashes use `password_hash` with `PASSWORD_ARGON2ID`, so the PHP build must provide Argon2id: missing Argon2id via password_hash documented; no requirement that the PHP build provides Argon2id //important//
- Selection: only `z_user` rows with `password_scheme = 'legacy'` and a non-empty `password` are processed, so passwordles: missing Legacy rows wrapped; non-empty password condition excluding passwordless SSO/invite rows missing //should-do//
- Prerequisite: needs the `2026-05-30_password_scheme.sql` migration (column `password_scheme`) to have run, which `db:mig: missing Schema migration runs automatically noted; file name 2026-05-30_password_scheme.sql missing //should-do//
- `password`: `VARCHAR(255) DEFAULT NULL` holding the stored hash (Argon2id for `native`, SHA-512 hex for `legacy`, Argon2: missing VARCHAR(255) DEFAULT NULL type and legacy as SHA-512 hex not explicit //extra-effort//
- `password_scheme`: `VARCHAR(32) DEFAULT 'legacy'` with comment 'hashing scheme only'; values `native`, `legacy`, `onion`: missing VARCHAR(32), DEFAULT 'legacy' and since-date not stated //should-do//
- `password_scheme` writes: `z_userModel::add` inserts NULL and promotes it to `native` when a password is set.: missing z_userModel::add NULL-then-promote-to-native mechanism not stated, only outcome //extra-effort//
- `last_password_rehash_at`: `TIMESTAMP NULL`, set on every password change or rehash and backfilled from `created` for ro: missing TIMESTAMP NULL type and backfill from created for existing rows not stated //extra-effort//
- `salt`: `VARCHAR(255) DEFAULT NULL`, per-row salt of `legacy`/`onion` hashes, set to NULL when the hash is upgraded to `: missing Salt semantics stated; VARCHAR(255) DEFAULT NULL column type absent //extra-effort//
- `2026-05-30_password_scheme.sql`: Adds `password_scheme` and `last_password_rehash_at`, sets scheme NULL for passwordles: missing Backfill of last_password_rehash_at from created and file name not stated //extra-effort//
- Length rule: `Password::hash` accepts 3 to 1024 bytes and throws `InvalidArgumentException("Invalid password length.")` : missing Concrete 3 to 1024 byte limits and exact exception message missing //should-do//
- Constants: `NATIVE = "native"`, `LEGACY = "legacy"`, `ONION = "onion"`, `MIN_LENGTH_BYTES = 3` and `MAX_LENGTH_BYTES = 1: missing Constant values (min 3, max 1024) and NATIVE/ONION constants not stated //should-do//
- Algorithm: Argon2id (`PASSWORD_ARGON2ID`) at PHP's default cost; it needs a PHP build with Argon2id support.: missing Requirement of a PHP build with Argon2id support not stated //should-do//
- Length rule: Throws `InvalidArgumentException("Invalid password length.")` outside 3-1024 bytes, measured with `strlen` : missing 3-1024 limits, exact message and strlen byte measurement not stated //should-do//
- Schemes `legacy` and `onion`: Need a non-empty `$salt` or `InvalidArgumentException("A legacy or onion hash requires a s: missing InvalidArgumentException for missing salt on legacy/onion not mentioned //should-do//
- `Password::onionWrap(string $legacyHash): string` [internal]: Wraps the stored SHA-512 hex in Argon2id with reduced cost: missing Reduced wrap cost (memory_cost 12288, time_cost 1, threads 1) not stated //optional//
- `isUpgradeNeeded(): bool`: True when the match is correct but the stored hash is stale or legacy.: missing Does not state it requires a correct match or covers legacy //should-do//
- Purpose: Verify-only shim for the retired `zubzet/password-hash-utilities` "0.9" plus SHA-512 scheme, kept until no `leg: missing LegacyHash shim, zubzet/password-hash-utilities and keep-until-no-legacy-rows not named //nice-to-have//
- `legacy`: `password` is the raw SHA-512 hex and `salt` is the per-row salt.: missing Legacy password being raw SHA-512 hex not stated; only 'previous scheme' //should-do//
- Bulk wrap: `getLegacyPasswords()` selects `legacy` rows with a non-empty hash and `onionWrapPassword()` rewrites them as: missing Only non-empty legacy rows wrapped; getLegacyPasswords/onionWrapPassword not named //nice-to-have//

### `docs/core-features/permission-system.md` (10)
- Gate exit: a failing check without `$boolResult` renders the error or login route and calls `exit`.: missing Login route for anonymous users not mentioned; only 403 plus cancelled request //important//
- `user`: the current request's `User` object, overwriting any caller-supplied `user` key.: missing Overwriting of any caller-supplied 'user' key not stated //should-do//
- Name format: Free-form dot-separated string stored in `z_role_permission.name` or `z_user_permission.name` (`VARCHAR(255: missing Storage in name columns (VARCHAR(255)) and free-form, unenforced format not stated //important//
- `*.*` wildcard: A held `*.*` satisfies every checked permission, including single-segment names such as `support`.: missing Only says all permissions; single-segment names like support not stated //important//
- Trailing `.*` wildcard: A held `a.*` satisfies a check for `a` itself and for any name beneath it such as `a.b` or `a.b.: missing Held a.* also satisfying the bare name a not stated //important//
- Nested prefix wildcard: A held `a.b.*` satisfies `a.b` and `a.b.c` but not `a.x`.: missing a.b.* satisfying a.b itself but not a.x not stated //should-do//
- `user()->checkPermission($permission): bool`: Returns `false` for anonymous users and never redirects.: missing Returns false for anonymous users not stated //important//
- 403 page: Shown by `Request::checkPermission()` and `checkSuperPermission()` when a logged-in user lacks the permission,: missing checkSuperPermission not mentioned; only logged-in users reach the 403 page //important//
- Missing permission: The `error/403` page is rendered with HTTP status 403 and execution stops.: missing Says 403 page; HTTP status 403 not stated //should-do//
- Wildcards: Holders of `*.*` or `admin.*` pass every panel check because of wildcard matching.: missing Wildcards documented but not tied to passing every panel check //should-do//

### `docs/core-features/query-builder/examples.md` (4)
- `dbSelect` table argument: `$table` may be a string, a string with alias (`"z_user u"`) or an array (`["u" => "z_user"]`: missing String-with-alias form ("z_user u") not shown //should-do//
- Conditions: `where()` with array conditions, operator suffixes in keys (`"u.email LIKE"`, `"u.id <"`, `"u.id IN"`) and n: missing No explicit nested AND group; IN suffix only in complex example //should-do//
- Joins: `join()` and `leftJoin()` with an alias to `table`, `conditions` and `type` (`LEFT`) arrays.: missing Generic join() with table/conditions/type arrays not shown //should-do//
- Paging and ordering: `limit()`, `offset()`, `page()`, `orderAsc()` and `orderDesc()`.: missing page() not shown //should-do//

### `docs/core-features/query-builder/index.md` (12)
- `cakephp/database` `^4.5`: query builder and value binder wrapped by `CanBuildQuery` and `Connection`.: missing CakePHP Database named; version ^4.5 and CanBuildQuery/Connection wrapping not stated //should-do//
- `exec()` with a CakePHP `Query`: runs it through `Connection::execQuery()` and ignores `$types` and `$params`.: missing $types and $params ignored, routing via Connection::execQuery not stated //should-do//
- Query builder methods: `dbSelect`, `dbInsert`, `dbUpdate`, `dbDelete` and `getQueryBuilder` come from trait `CanBuildQue: missing CanBuildQuery trait unnamed; getQueryBuilder snippet uses nonexistent db()->cakePHPDatabase //important//
- `$queryBuilderConnection`: `Cake\Database\Connection` created with only `driver => Mysql::class`, used purely to build a: missing Property queryBuilderConnection and driver-only Mysql config not stated; docs use wrong name //nice-to-have//
- Purpose: Compiles a query-builder object with `ZubZetValueBinder` and runs it through `exec()`, returning the `Connectio: missing db()->execQuery() and ZubZetValueBinder compile step not named //should-do//
- Query objects: When `$query` is a `Cake\Database\Query`, `$types` and `$params` are ignored.: missing $types and $params ignored for Query objects not stated //should-do//
- Availability: Provides `dbSelect()`, `dbInsert()`, `dbUpdate()`, `dbDelete()` and `getQueryBuilder()` to `z_model` subcl: missing Base Controller lacking the trait not stated; seed-class availability only implied elsewhere //important//
- `dbSelect($fields = [], $table = [], array $types = [])`: Returns a `Cake\Database\Query\SelectQuery`.: missing Return class SelectQuery not named; docs say generic 'CakePHP Query object' //important//
- `dbInsert(?string $table = null, array $values = [], array $types = [])`: Returns an `InsertQuery`; chain `->values([...: missing InsertQuery return and chaining ->values() for more rows not shown //important//
- `dbUpdate($table = null, array $values = [], array $conditions = [], array $types = [])`: Returns an `UpdateQuery`; add : missing Return class UpdateQuery not named; docs say generic 'CakePHP Query object' //important//
- `dbDelete(?string $table = null, array $conditions = [], array $types = [])`: Returns a `DeleteQuery`; add `->where([...: missing Signature shows required string $table; code is ?string $table = null //important//
- Execution path: Pass it to `$this->exec($query)` or `db()->execQuery($query)` and then use the result helpers.: missing db()->execQuery() path and using result helpers afterwards not stated //important//

### `docs/core-features/rest-api.md` (14)
- Methods that end the script: `success()`, `error()`, `formErrors()`, `generateRest()` (default), `generateRestError()`, : missing rerouteUrl() and logout() exiting not mentioned; error/success/formErrors exit only implied //important//
- Methods that keep running: `json()`, `render()`, `setCookie()`, `unsetCookie()` and a non-final `reroute()` return norma: missing Only json() documented as non-exiting; render, cookie helpers, non-final reroute missing //should-do//
- Unencodable data: for example a resource throws `\JsonException`, after the header was already sent.: missing JsonException is thrown after the Content-Type header was already sent //should-do//
- `generateRestError($code, $message)`: logs a `REST_ERROR` warning, then outputs only `{"error":{"code":...,"message":...: missing REST_ERROR warning log, exit and HTTP status staying 200 not stated //important//
- `success($payload = [])`: sends `{"result":"success", ...payload}` and exits; payload keys are merged after `result` and: missing Payload merged after `result` so keys can override it; exit only in forms page //important//
- Envelope: builds a `meta` block (`endpoint` = `REST API`, `request` = URL parts joined by `/`, `timestamp` = `time()`) a: missing meta shape shown with placeholders; request = URL parts joined by `/`, time() not defined //should-do//
- Behavior: echoes `json_encode($data, JSON_PRETTY_PRINT)` and calls `exit` unless `$die` is false.: missing $die shown via generateRest only; pretty-printed json_encode output not stated //should-do//
- No headers: sets neither a `Content-Type` header nor an HTTP status code, unlike `Response::json()`.: missing json() header noted; that generateRest sends no Content-Type or status not stated //important//
- `ShowError($code, $message)`: replaces the payload with an `error` object holding `code` and `message` (meta is dropped): missing error shape shown via generateRestError; Rest::ShowError and its forced exit not named //should-do//
- Response shape: Pages render with `layout/min_layout.php` and JSON actions answer `{"result":"success"|"error","message": missing Login controller's min_layout.php rendering and HTTP 200 meta for errors not stated //should-do//
- JSON content-type pitfall: A response sent with `Response::json()` carries `application/json`, which jQuery pre-parses, : missing jQuery pre-parses application/json, breaking Z.Request.action and ZForm.send; pitfall not mentioned //important//
- REST envelope: `generateRest` output is pretty-printed JSON that also carries a `meta` object with `endpoint`, `request`: missing Pretty-printed output and absence of JSON Content-Type header not stated //should-do//
- `success` result: `$res->success($payload = [])` returns `{result: success, ...payload}` and triggers `saveHook` in `ZFo: missing Success triggering ZForm saveHook not stated //important//
- `generateRestError($code, $message)`: Returns `{error: {code, message}}`, which `Z.Request.root` handlers can read as `r: missing Client-side Z.Request.root reading res.error.message not documented //should-do//

### `docs/core-features/routing.md` (24)
- `nikic/fast-route` `^1.3`: route dispatcher behind `Route` and `Router`, which replaced Slim in 1.2.0.: missing FastRoute and Slim replacement named; version ^1.3 not stated //should-do//
- Removed dependencies: Slim and `zubzet/password-hash-utilities` are no longer listed, matching the 1.2.0 changelog.: missing Slim replacement noted; removal of zubzet/password-hash-utilities not stated //should-do//
- `app/Routes/`: default `routes` directory whose top-level `*.php` files are `require_once`d when the route dispatcher is: missing Routes folder files auto-registered; top-level-only glob and require_once not stated //important//
- Routes: every top-level `app/Routes/*.php` file is loaded and the file name carries no meaning.: missing All files in Routes folder registered; top-level-only and file-name irrelevance not explicit //important//
- `routes`: default `app/Routes/`; every top-level `*.php` file in it is included once when the route dispatcher is first : missing setting key `routes`, top-level-only and include-once behaviour not stated //should-do//
- Load order: `glob($routes . "/*.php")` user files load first, then framework `IncludedComponents/routes/*.php`, each via: missing Load order (user before bundled framework routes) and framework route files not stated //important//
- File selection: only top-level `.php` files are loaded, any file name works, order is glob (alphabetical) order, and sub: missing Top-level .php only, alphabetical order, no sub-directory scanning not stated //important//
- Usage: route files call the static facade after `use ZubZet\Framework\Routing\Route;`.: missing `use ZubZet\Framework\Routing\Route;` import never shown //important//
- Signature: `Route::get`, `post`, `put`, `patch`, `delete`, `options` and `any` take `(string $endpoint, array|callable $: missing Closure/callable actions and PendingRoute return type not documented //important//
- `Route::any()`: registers GET, POST, PUT, PATCH, DELETE, OPTIONS and HEAD.: missing Exact verb list behind any() (including HEAD) not stated //should-do//
- `Route::define(string $method, string $endpoint, array|callable $action, array $arguments = [])`: registers any verb str: missing Upper-casing of the verb and accepting 'ANY' not stated //should-do//
- Endpoint syntax: FastRoute standard `{name}` (regex `[^/]+`), `{name:regex}` and optional trailing `[...]` segments.: missing {name:regex}, optional [...] segments and default [^/]+ pattern not documented //important//
- Execution: runs through `executeControllerAction()` with `($req, $res, ...$arguments)`.: missing Args after Request/Response stated; executeControllerAction() not mentioned //should-do//
- Method name: any public method works, the `action_` prefix is not required for explicit routes.: missing Not stated that any public method works and action_ prefix is not required //important//
- Stage scope: arguments belong to one stage only, so action, middleware and afterware each receive their own list.: missing Not stated that arguments are scoped per stage (action, middleware, afterware) //should-do//
- `{name}` placeholders: passed as `$args` and stored in `$req->urlParameters` before every controller stage (action, midd: missing Availability in middleware/afterware stages and $req->urlParameters storage not stated //important//
- `Request::getRouteParameter($key = null)`: returns the whole associative array, one value, or `null` when the key is mis: missing Returns null when the key is missing not stated //important//
- Group prefix placeholders: `{userId}` style placeholders in prefixes (e.g. `/accept-afterware-parameters/{userId}/{postI: missing Prefix placeholders reaching group middleware and afterware not stated //should-do//
- `Route::group(string $prefix = "", ?callable $callback = null)`: returns a `PendingGroup` that accepts `->middleware()` : missing Defaults (empty prefix, optional callback) and PendingGroup return type not documented //important//
- Nesting: prefixes of nested groups are concatenated in order and middleware of all enclosing groups are inherited by con: missing Middleware inheritance across nested groups not stated; only prefix concatenation shown //important//
- Signatures: `->middleware(array $middleware, array $arguments = [])` and `->afterMiddleware(array $afterMiddleware, arra: missing Not stated that closures are rejected; only [Class::class, 'method'] shown //important//
- Execution order for an explicit route: group middleware (outer to inner), route middleware, action, route afterware, gro: missing Order of group vs route middleware and nested outer-to-inner order not stated //important//
- Afterware: return values are ignored, and it does not run when a middleware blocked the request or when the action ends : missing Afterware return values ignored and skipped after exit (generateRest, rerouteUrl) not stated //should-do//
- Shape: ordinary public controller methods with any name, taking `($req, $res, ...$arguments)`.: missing Not stated that any public method name works with ($req, $res, ...$arguments) //should-do//

### `docs/core-features/views.md` (7)
- `app/Views/`: default `z_views` directory holding views, mail templates and the `layout/` sub-folder, with further sub-f: missing Views live in z_views; app/Views default, mail templates, layout/ sub-folder not stated //important//
- View files: views live in `app/Views/` and are addressed relative to it with `.php` optional.: missing Views belong in z_views; relative addressing and optional .php not stated //important//
- `body` key: closure printing the page content, replaced by an empty closure when missing.: missing Empty-closure default when body key is missing not stated //important//
- `head` key: closure printing extra `<head>` markup, replaced by an empty closure when missing.: missing Empty-closure default when head key is missing not stated //important//
- Location: views live below the `z_views` directory (default `app/Views/`), sub-folders are allowed and a view is address: missing Default app/Views/ only in upgrade note; relative addressing of sub-folders not stated //important//
- `$document`: view path relative to the views directory with `.php` optional.: missing .php extension optional not stated; relative path only implied //important//
- `$opt`: associative array whose keys are available as `$opt[...]` in the view and layout closures.: missing Layout closure access to $opt only shown in layouts example, not stated //important//

### `docs/forms/auto-form-validation.md` (76)
- `FormField`: alias of `Form\Validation\Field`.: missing new FormField(...) used throughout; alias of Form\Validation\Field not stated //should-do//
- Purpose: returns a JSON string of `[{"value","text"}]` for select and multi-select inputs.: missing Returns a JSON string; parameters ($table rows, value/text fields) not explained //should-do//
- `hasFormData()`: true when POST contains the key `isFormData`, which Z.js forms always send (`isFormData=true` or FormDa: missing Docs say 'any data'; real check is POST key isFormData sent by Z.js //important//
- `formErrors($errors)`: accepts any number of error arrays (non-arrays ignored), merges them and sends `{"result":"formEr: missing Variadic array merge, non-arrays ignored, {result:formErrors} envelope, exit not described //important//
- `insertDatabase(string $table, Result $validationResult, array $fixed = [])`: runs a prepared INSERT from `$fixed` colum: missing $fixed bound as strings, noSave skipping and returned insert id not stated //important//
- `updateDatabase(string $table, string $pkField, string $pkType, $pkValue, Result $validationResult, array $fixed = [])`:: missing WHERE pkField = pkValue semantics, $fixed handling and void return not described //important//
- `insertOrUpdateDatabase(...)`: selects the row by primary key, then updates it and returns `$pkValue` when found, otherw: missing Primary-key lookup and return of $pkValue versus new insert id only implied //should-do//
- Request detection: Pages are GET renders and writes are POSTs detected with `$req->hasFormData()` (field `isFormData`) o: missing POST fields isFormData and action not named; GET render versus POST write not stated //nice-to-have//
- `$fields` argument: The same `FormField` objects are stored in `FormResult::$fields` and each receives its validated `va: missing Same FormField objects stored in FormResult::$fields each receiving value only implied by example //should-do//
- `Request::hasFormData()`: True when POST contains `isFormData`, which every Z.js `ZForm` submission adds.: missing Docs say 'any data'; real check is POST key isFormData added by ZForm //important//
- Fluent chaining: Every rule method returns the field itself so rules can be chained.: missing Chaining only shown in examples; not stated every rule method returns the field //should-do//
- `dbField`: Column name used by `insertDatabase` and `updateDatabase`.: missing Property `dbField` not named; only second constructor argument used by updateDatabase //important//
- `value`: Validated value after `validateForm`, default `null`.: missing Default null and general 'validated value' meaning; only multi-select example shows ->value //should-do//
- `required()`: Fails with `required` when the input is unset or an empty string and no uploaded file exists under that na: missing Error key `required` and unset/empty-string/no-upload failure condition not stated //important//
- `length($min, $max)`: Inclusive bounds using `strlen` (bytes) for scalars and `count` for array values, with error `info: missing Inclusive bounds and error info [min, max] not stated //important//
- `regex($expression, $exceptions = [])`: Fails with `regex` when text remains after the `$exceptions` strings are removed: missing Failure semantics (allowed-char pattern, exceptions removed first) not described //important//
- `exists($table, $field)`: Fails with the error key `exist` (not `exists`) when `db()->checkIfExists` finds no matching r: missing Error key is `exist` (not `exists`); failure when no matching row //important//
- `exists` arrays: Every picked item must exist and the error is reported once for the field.: missing Error reported once per field not stated //should-do//
- `in(array $allowedValues)`: Fails with `in` unless the value is in the in-memory allow-list using non-strict `in_array`,: missing Non-strict in_array comparison and error key `in` not stated //important//
- `checked()`: Sugar for `in(['1', 'true', 'on'])` that enforces a ticked checkbox and reports the error key `in`.: missing Error key `in` reported by checked() not stated //important//
- Scalar-only rules: `filter`, `unique`, `integer`, `range` and `date` do not support array values.: missing Scalar-only rules (filter, unique, integer, range, date) not named; only list-aware ones //should-do//
- `errors`: Array of error entry arrays, default empty.: missing Entry array shape and default empty array not described //should-do//
- `fields`: The validated `FormField` objects, or the rule list for a CED result.: missing CED result holds the rule list instead of validated fields //nice-to-have//
- Result: Inserts one row from the validated fields and returns `getInsertId()`.: missing Return value (getInsertId) not stated //important//
- Column mapping: Columns come from non-`noSave` fields using `dbField` and bind types from `dataType`.: missing noSave skipping and dataType bind types not stated //important//
- Result: Updates the row where `$pkField` equals `$pkValue` from the validated fields and returns nothing.: missing WHERE pkField = pkValue update semantics and no return value not described //important//
- Result: Selects by primary key, updates and returns `$pkValue` if found, otherwise inserts and returns the new id.: missing Primary-key select and returned $pkValue versus new id only implied by example //should-do//
- `Response::formErrors(...$errors)`: Sends `{result: formErrors, formErrors: [...]}` merging every array argument, drops : missing Merging several array args, dropping non-arrays, envelope shape and exit not stated //important//
- File and globals: `web/Z.js` is a single non-module script defining the global `Z` namespace plus the classes `ZForm`, `: missing Classes ZFormField, ZCED, ZCEDItem, zInputIndex and single non-module global script not listed //should-do//
- Bootstrap 4 markup: Generated DOM relies on Bootstrap 4 classes such as `form-row`, `custom-file`, `input-group-prepend`: missing Only form-check mentioned; no statement that Z.js needs Bootstrap 4 classes (form-row, custom-file, badge-primary) //should-do//
- `formErrors` result: `$res->formErrors($errors)` returns `{result: formErrors, formErrors: [...]}` and each entry is map: missing JSON shape {result: formErrors, formErrors: [...]} and mapping of entries by name not described //important//
- `isFormData` marker: Added as `1` to the `FormData` (or `true` in the urlencoded string) and detected by `Request::hasFo: missing isFormData marker (value 1) not named; hasFormData described only as 'any data in the request' //should-do//
- Transport: `ZForm.send` posts `multipart/form-data` through `$.ajax` with `contentType: false`, `processData: false` and: missing multipart/form-data via $.ajax (contentType, processData, cache false) not described //should-do//
- Target URL: Posts go to the current URL unless `customEndpoint` or the `send(customUrl)` argument is set, which is passe: missing customEndpoint option and send(customUrl) override not documented //should-do//
- `saveHook`: Called with the parsed JSON response after a success only, or with `getValues()` in `collectOnly` mode.: missing Called only after success with parsed JSON response; not in an options table //important//
- Behaviour: Collects `getFormData()` and posts it, then handles the JSON `result`.: missing send(), getFormData() and JSON result handling not described //important//
- Result handling: `success` runs `saveHook`, optionally reloads and shows the saved hint, `formErrors` marks fields then : missing Per-result handling (reload, saved hint, formErrorHook, saveError hint) not described //important//
- `addCustomHTML(html)`: Appends raw HTML in a `div` and starts the next field on a new row.: missing Next field starting on a new row not stated //should-do//
- `addSeperator()`: Appends an `hr` and breaks the row; the method name is spelled with an e.: missing Not stated that it also breaks the current row //should-do//
- Row wrapping: Fields flow in a `form-group` and `form-row`, and a new row starts when the summed field widths exceed 12.: missing New row when summed field widths exceed 12 not stated //should-do//
- Layout rebuild: A field `hide()` or `show()` rebuilds the layout from the ordered item list while keeping custom HTML, s: missing Spacers and rebuild-from-ordered-item-list not mentioned //nice-to-have//
- `buttonSubmit`: The built-in submit button with classes `btn btn-primary` and text `Z.Lang.submit`.: missing Classes btn btn-primary and text Z.Lang.submit not stated //should-do//
- `options.resetUnknown`: When true `reset()` is called on the whole form before values are applied.: missing Docs say unknown fields reset; whole-form reset() not stated //should-do//
- `reset()`: Resets every field to its `default` or empty value and keeps select options, while CEDs are not reset.: missing form.reset() not described: default or empty value, select options kept, CEDs not reset //should-do//
- `enable()`, `disable()` and `isDisabled()`: Manage the form-level disabled state, and `isDisabled()` is also true while : missing form.isDisabled() and it being true while sending not documented //should-do//
- Public properties: `fields` (map by name), `dom`, `alert`, `inputSpace`, `buttonSubmit`, `options` and `meta` are used b: missing fields map, alert, inputSpace and options properties not documented //should-do//
- `name`: POST key and `name` attribute of the input.: missing Only POST name stated; input name attribute not mentioned //important//
- `type`: One of the HTML input types or `select`, `multi-select`, `textarea`, `autocomplete`, `button` and `hidden`; an o: missing button and hidden types, and invalid type attribute when type omitted, not stated //important//
- `text`: Label HTML, default a non-breaking space.: missing Label accepts HTML; default is a non-breaking space //important//
- `placeholder`: Input placeholder, and for `multi-select` the text of the first option.: missing Only multi-select placeholder shown in example; general input placeholder not described //important//
- `default`: Value restored by `reset()` and used as initial value when `value` is empty.: missing Not in options table; used as initial value when value is empty not stated //important//
- `value`: Initial value that takes precedence over `default` when truthy (so `0` or an empty string falls back), and the : missing Precedence over default when truthy (0/empty falls back) and button label role not stated //important//
- `width`: Bootstrap grid units 1 to 12 applied as `col-md-N`, default 12 and 0 for `hidden`.: missing Default 12, 0 for hidden, and col-md-N class not stated //important//
- `attributes`: Attribute map set on the input element after the defaults, so it can override them.: missing Applied after defaults so it can override them not stated //important//
- `prepend`: HTML placed in an `input-group-prepend` before the input.: missing HTML content and input-group-prepend wrapper not stated //important//
- DOM structure contract: Each field is `div.col.col-12.col-md-N` containing label, input and `span.form-text.text-danger`: missing div.col.col-12.col-md-N structure, field.label and closest('.form-group') contract not documented //should-do//
- Single-control wrappers: `file` adds `div.custom-file`, `checkbox` adds `div.form-check` with the label after the input,: missing file div.custom-file and prepend div.input-group wrappers not described //nice-to-have//
- Composite wrappers: `multi-select` wraps the select and a badge `div.mt-2`, and `autocomplete` wraps the input and a sug: missing Wrapper structure (badge div.mt-2, suggestion div.list-group) not described //nice-to-have//
- Default types: Any native input type creates an `input` with class `form-control`.: missing form-control class on generated input not stated //important//
- `select`: Creates a select with a disabled placeholder option `---` until `feedData` replaces it.: missing Disabled '---' placeholder option until feedData not stated //important//
- `multi-select`: Select plus removable badges where the value is an array of strings, and duplicates are ignored.: missing Duplicate picks being ignored not stated //important//
- Multi-select picking: A picked option is hidden in the dropdown, the dropdown returns to the placeholder and an optgroup: missing Dropdown returning to placeholder after pick not stated //nice-to-have//
- Multi-select badges: Badges are `badge badge-primary` spans with a `data-value` attribute that remove the value on click: missing badge-primary markup, data-value attribute and no removal when disabled not stated //nice-to-have//
- Multi-select value: The setter accepts only arrays and clears the selection for any other input, converting items to str: missing Items converted to strings not stated //should-do//
- Multi-select reset: Restores `default` or an empty array.: missing Reset to empty array when no default not stated //nice-to-have//
- `textarea`: Creates a `textarea` with class `form-control`.: missing form-control class on the textarea not stated //should-do//
- `autocomplete`: Text input with a suggestion list shown from `autocompleteMinCharacters` (default 2) typed characters.: missing Default of 2 for autocompleteMinCharacters not stated //should-do//
- `autocompleteData`: Array of plain strings filtered by case-insensitive substring match, or a backend path string.: missing Case-insensitive substring filtering not stated //should-do//
- Backend suggestions: A path string triggers `Z.Request.root(path, 'autocomplete', {value})` on each key press and expect: missing Request uses Z.Request.root with action 'autocomplete'; result used from next key press //should-do//
- `autocompleteTextCB`: Called with the highlighted HTML and the raw entry and its return value is used as the item HTML.: missing First argument is highlighted HTML and return value becomes item HTML not stated //nice-to-have//
- `autocompleteCB`: Called with the raw entry when the user picks a suggestion.: missing Callback argument (raw entry) not described; only 'on click' stated //nice-to-have//
- Select food format: Entries are objects with `value`, `text` and optional `type` (`option` or `optgroup`), where `value`: missing value falling back to text and explicit type 'option' for select not stated //important//
- `value` getter and setter: Multi-select returns a copy of the array, checkbox returns a boolean and other types read the: missing Copy semantics of multi-select getter and other-types behaviour not stated //important//
- `reset()`: Restores `default` or an empty value, `false` for checkbox and `[]` for multi-select.: missing Field reset(): empty value, false for checkbox, [] for multi-select not described //should-do//
- `disable()`, `enable()` and `isDisabled()`: Per-field disabled state that is also true while the owning form is disabled: missing isDisabled also true while owning form disabled not stated //should-do//
- `Controller::makeFood($table, $valueField, $textField, $optionalTextField = null)`: Takes an array of rows and returns a: missing Signature, JSON string return and optional second text column appended with space not described //important//

### `docs/forms/ced-validation.md` (16)
- Purpose: builds a hand-written JSON-like string `[{"dbId":"<id>","<field>":"<value>"},...]` for CED items.: missing Output format [{"dbId":...}] and parameters not described //should-do//
- `doCED($table, $validationResult, $fix = [])`: applies posted Create-Edit-Delete items, where `create` inserts, `edit` u: missing Per-item create/edit/delete semantics by Z action and error on unknown action //should-do//
- Base class: Extends `z_controller` and reuses `makeFood()` and `makeCEDFood()` to feed select and CED inputs.: missing makeFood() and ZController's reuse of makeFood/makeCEDFood not stated //nice-to-have//
- Save form: Field `name` (required, length 3 to 100) and a `permissions` CED with field `name` (required, length 3 to 100: missing Same CED example, but not tied to the panel's role editor //should-do//
- Delete entries: Rules apply to every entry regardless of its `Z` action.: missing Delete-action entries are validated too, regardless of Z action //should-do//
- Purpose: Applies the CED entries of `getPost($name)` to `$table` according to each entry `Z` action and returns immediat: missing Per-entry Z action handling and early return on doNothing not described //important//
- `Z` = `create`: Inserts a row using the field `name` as column name (not `dbField`) and `dataType` as bind type, appendi: missing Uses field name (not dbField), dataType bind types, $fix bound as strings //important//
- `Z` = `delete`: Soft delete via `UPDATE <table> SET active = 0 WHERE id = ?`, so the table needs an `active` column.: missing Delete runs UPDATE SET active = 0 WHERE id = ?; docs only name the active column //important//
- Table requirements: The table needs an `id` primary key and an `active` column.: missing Required `id` primary key not stated; only `active` column //should-do//
- `createCED(blueprint)`: Creates a `ZCED`, adds it and returns it.: missing Return value (ZCED) not stated; blueprint options differ from createField options //important//
- `name`: POST array name used by `validateCED` and `doCED`.: missing validateCED takes name; doCED use and POST array role not stated //important//
- `text`: Label HTML of the CED.: missing text as label HTML not described for CED //should-do//
- `fields`: Required array of `createField` option objects that each item repeats.: missing 'Required' and per-item repetition of fields not stated //important//
- `value`: Initial rows as objects with `dbId` plus values keyed by field name, and a key without a matching field throws.: missing Row shape (dbId plus field-name keys) and throw on unknown key not stated //important//
- `compact`: Renders items as bootstrap rows instead of cards.: missing Items rendering as rows instead of cards not stated //should-do//
- `Controller::makeCEDFood($table, $fields, $escape = null)`: Returns a JS array literal of rows with `dbId` from the `id`: missing Signature, dbId from id column and fields list not described //important//

### `docs/forms/file-uploads.md` (14)
- Trailing slash needed: the generated file name is appended directly to the folder string.: missing trailing slash stated for upload() only, not for uploadFolder setting //urgent//
- `z_upload`: alias of `Form\Upload`.: missing upload() returns z_upload object; alias of Form\Upload not stated //should-do//
- Upload step: file fields are uploaded first; a field without a posted file is flagged `noSave`, an upload error answers : missing noSave when no file, 'Upload error: <code>' answer, file id as field value //important//
- Megabyte sizes in bytes: `FILE_SIZE_1MB`, `FILE_SIZE_2MB`, `FILE_SIZE_5MB`, `FILE_SIZE_10MB`, `FILE_SIZE_20MB`, `FILE_SI: missing only says some constants exist; full FILE_SIZE_*MB list and byte values missing //extra-effort//
- Global class aliases: `FormField` maps to `Form\Validation\Field`, `FormResult` to `Form\Validation\Result` and `z_uploa: missing z_upload named as upload() return; FormField/FormResult alias class mapping not stated //important//
- `file($maxSize, $types = [])`: Validates an upload from `getFiles()`, sets `isFile` and is needed for the automatic uplo: missing Reads getFiles(), sets isFile, required for automatic upload in insertDatabase //important//
- `file` `$maxSize`: Byte limit compared with `size > maxSize` using the `FILE_SIZE_*` constants; unlike `Upload::upload` : missing Byte comparison with size and that 0 does not mean unlimited here //important//
- `file` `$types`: Allowed extensions compared with the lowercased file extension, so entries must be lowercase without a : missing Lowercase extensions without dot; empty array allows any type //urgent//
- Trigger: Runs first in the insert and update helpers for fields marked with `->file()`.: missing Insert/update helpers run the upload first for ->file() fields not stated //should-do//
- Purpose: Validates and moves an uploaded file into `$uploadDir`, registers it in `z_file` and returns an `UPLOAD_*` code: missing UPLOAD_* return codes and z_file registration not described //important//
- `$uploadDir`: Must end with a slash because it is concatenated directly, and a missing directory is created with `mkdir(: missing Missing directory auto-created with mkdir 0755 not stated //important//
- `$maxSize`: Byte limit where 0 means unlimited.: missing Unit is bytes and 0 means unlimited not stated //important//
- `$typeArray`: Lowercase extensions without dot, and an empty array allows every extension including `php`.: missing Lowercase extensions without dot; empty array allows every extension including php //urgent//
- `FILE_SIZE_*`: `FILE_SIZE_1MB`, `2MB`, `5MB`, `10MB`, `20MB`, `50MB`, `100MB`, `200MB`, `500MB`, `1GB`, `2GB`, `5GB`, `1: missing Full FILE_SIZE_* list not enumerated; only 1MB and 100GB appear in examples //important//

### `docs/frontend-integration/backend-requests.md` (4)
- Comparison: loosely compares POST `action` with `$type`; `Z.Request.action()` and `Z.Request.root()` send that field and: missing POST `action` field, loose comparison, ignored GET action not stated //important//
- Result envelope consumed by Z.js: `result` is `success`, `error` or `formErrors`, and each form error entry holds `name`: missing error/formErrors result values and entry fields name, type, info not stated //important//
- `Request::isAction(string $type)`: True when POST `action` equals the type, matching `Z.Request.action()` calls.: missing POST `action` field comparison not named; only 'initiated by Z.Request.action' described //important//
- Behaviour: POSTs `data` plus the post field `action` to the current URL and parses the JSON text response before calling: missing POST to current URL and JSON parsing of response before handler not stated //important//

### `docs/frontend-integration/presets.md` (5)
- Automatic inclusion: `essentialsHead` adds Z.js to every layout that calls the layout essentials, so it is not included : missing Layout essentials hook (layout_essentials_head) that adds Z.js not named; custom layouts must call it //important//
- Request: Posts `name` and `password` to the `login` route with action `login`.: missing Posted fields name/password to login route with action login not stated //should-do//
- Success: Reloads the page, or goes to `redirect` when it is not empty.: missing Page reload when no redirect is given not stated //should-do//
- Error display: Hides then shows the error label, maps two known server messages to `Z.Lang`, and shakes the label when t: missing Mapping of two server messages to Z.Lang and shake versus slideDown behaviour not described //nice-to-have//
- Request: Posts `email` and `password` merged with `additionalData` to `login/signup` with action `signup`.: missing Posts email/password plus additionalData to login/signup with action signup not stated //should-do//

### `docs/guides/email.md` (9)
- Mail layouts: `sendEmail()` strips `.php` and `_layout` from the given name and re-appends `_layout.php`.: missing Mail layout file must end _layout.php; strip-and-re-append normalisation not stated //should-do//
- `mail_smtp`: no default; SMTP host passed to PHPMailer `Host`, and SMTP mode with `SMTPAuth` is always used.: missing only says it configures SMTP; PHPMailer Host mapping, always-on SMTPAuth missing //important//
- `$subject` as string: encoded as pre-encoded UTF-8 base64 word `=?utf-8?b?...?=`.: missing UTF-8 base64 encoding of string subjects not stated //should-do//
- `$lang`: language id such as `en`, `EN` or `DE_Formal`, lowercased (`DE_Formal` becomes `de_formal`), and `null` becomes: missing Lowercasing (DE_Formal to de_formal) and null becoming 'en' not stated //should-do//
- `$attachments`: array of file contents (not paths), string keys become file names, added with `addStringAttachment()`.: missing Contents not paths; string keys become file names not stated //should-do//
- Name normalisation: every `.php` and every `_layout` is removed and `_layout` appended, so `mail`, `mail_layout` and `la: missing Normalisation making mail, mail_layout and layout/mail_layout.php equivalent not stated //should-do//
- Sender: the address is `mail_from`, falling back to `mail_user`, with `pageName` as sender name.: missing mail_from fallback to mail_user and pageName as sender name not stated //should-do//
- Connection settings read: `mail_smtp`, `mail_user`, `mail_password`, `mail_port` and `mail_security` (default `tls`), de: missing mail_port not mentioned; tls default only in 0.9-to-0.10 note //should-do//
- Placeholder convention: keys supplied through env use placeholder values such as `env` or `ENV` so the override can appl: missing ENV values shown but placeholder-for-env-override convention not explained //optional//

### `docs/guides/guest-list.md` (2)
- Naming: file `app/Models/<Name>Model.php` with global-namespace class `<Name>Model` (`getModel('<Name>')` appends `Model: missing Default app/Models dir and getModel() appending 'Model' suffix not stated //important//
- File and class naming: `<Name>Model.php` in `z_models` (default `app/Models/`) holding a class `<Name>Model` in the glob: missing Default app/Models dir and global-namespace class requirement not stated //should-do//

### `docs/guides/layout.md` (2)
- Role: full HTML5 page and the framework default layout.: missing Only that it is the default; full HTML5 page content not described //should-do//
- Overriding: a same-named file in `app/Views/layout/` replaces any bundled layout.: missing Override of all bundled layouts via app/Views/layout/ not stated; default layout only //should-do//

### `docs/guides/library.md` (2)
- Role picker: Without an id it renders `administration/role_select.php` with active roles where `is_group = 0` and a `Cre: missing Only active non-group roles listed and the confirm dialog not stated //nice-to-have//
- Create: POST action `create` additionally needs `admin.roles.create`, inserts an empty role and answers `{"roleId": n}` : missing POST action create, empty-name role and {roleId} response not described //should-do//

### `docs/index.md` (1)
- API reference: generated from PHPDoc by phpDocumentor into `docs/api` only inside CI, and `@internal` PHPDoc tags mark n: missing phpDocumentor generation into docs/api in CI only, and @internal meaning, not stated //nice-to-have//

### `docs/setup/installation.md` (10)
- MySQL or MariaDB only: the runtime link is `mysqli`, the query-builder driver is `Cake\Database\Driver\Mysql`, and no ot: missing Only MariaDB in dev stack; no MySQL/MariaDB-only statement, mysqli or Cake MySQL driver //important//
- `allow_env_config`: absent means off; set `allow_env_config = true` in the INI (or via `$params`) to honour `CONFIG_*` v: missing Absent-means-off and $params route not stated; only 'true' enables //important//
- Variable name: `CONFIG_` plus the upper-cased key, e.g. `CONFIG_DBHOST`, `CONFIG_MAIL_SMTP`, `CONFIG_ASSETVERSION`, `CON: missing only `CONFIG_*` pattern; upper-cased key rule and concrete examples not stated //important//
- `shell`: opens `bash` in the `application` container with `docker exec -it`.: missing bash and docker exec -it not stated; page describes skeleton project //nice-to-have//
- `seed`: runs `docker exec application php index.php db:seed`.: missing does not state it wraps docker exec application php index.php db:seed //nice-to-have//
- Compose file: `tests/e2e/packaging/docker/docker-compose-base.yml` defines services `application`, `database`, `database: missing service names application, database, database_dashboard, mailer not listed //nice-to-have//
- Service `database_dashboard`: `phpmyadmin:latest` on port 8081 preconfigured for host `database`.: missing phpMyAdmin on 8081 only; service name and preconfigured host `database` missing //nice-to-have//
- Service `mailer`: `rnwood/smtp4dev:latest` with its web UI on port 3300.: missing Service name mailer and image rnwood/smtp4dev:latest not stated //nice-to-have//
- Settings via environment: `CONFIG_HOST`, `CONFIG_ROOTDIRECTORY`, `CONFIG_DBHOST` (`database:3306`), `CONFIG_DBNAME`, `CO: missing generic CONFIG_* override; specific variables and DB host database:3306 not listed //nice-to-have//
- Placeholder convention: the e2e `z_settings.ini` uses placeholder values `env` or `ENV` for secrets and sets `allow_env_: missing env/ENV placeholder convention for secret keys not explained //nice-to-have//

### `docs/setup/upgrade/0.11-to-1.0.0.md` (11)
- Apache rewrite: the shipped rule is an Apache `.htaccess`, which needs `mod_rewrite` and `AllowOverride All`; other serv: missing .htaccess shown; mod_rewrite, AllowOverride All and non-Apache equivalent not stated //important//
- `webroot/assets/`: plain static files; bundled layouts link `{root}assets/img/favicon.png` and login views link `{root}a: missing assets/ moved into webroot; bundled favicon/loadCircle links and not-via-proxy rule not stated //should-do//
- `app/Controllers/`: default `z_controllers` directory holding the `<Name>Controller.php` files, one flat directory.: missing z_controllers to app/Controllers listed; naming and flat-directory rule not stated //important//
- `app/Models/`: default `z_models` directory holding the `<Name>Model.php` files, optionally with lower-case sub-folders.: missing z_models to app/Models listed; <Name>Model naming and lower-case sub-folders not stated //important//
- Script lookup order: it `require_once`s `index.php`, `zubzet.php` and `zubzet` in that order and stops at the first miss: missing Order exists only in copy-paste code; stop-at-first-missing behaviour not described //should-do//
- Rewrite rule: `RewriteCond %{REQUEST_FILENAME} !-f` with `RewriteRule . index.php [L,QSA]` sends every non-file request : missing Rule shown in upgrade steps; no explanation that all non-file requests route, QSA keeps query //important//
- Files outside the web root: `/app/Controllers/AdminController.php`, `/composer.json` and `/z_config/z_settings.ini` answ: missing Private files stay outside webroot; no 404 behaviour or example paths //urgent//
- `z_controllers`: default `app/Controllers/`; directory searched first for `<Name>Controller.php`, flat, with a trailing : missing default only as folder move; setting key, flat lookup, trailing slash not stated //should-do//
- `z_models`: default `app/Models/`; directory searched first for `<Name>Model.php` by `model()`, with a trailing slash re: missing default only as folder move; setting key, <Name>Model.php lookup, trailing slash not stated //should-do//
- `z_views`: default `app/Views/`; base directory for views, layouts and mail templates, so app files such as `404.php` or: missing default only as folder move; app files shadowing bundled views, mail templates not stated //should-do//
- File and class: `app/Controllers/<Name>Controller.php` holds the global-namespace class `<Name>Controller`, in one flat : missing Only folder move noted; naming rule, global namespace and flat directory not stated //important//

### `docs/setup/upgrade/0.9-to-0.10.md` (1)
- `mail_security`: default `tls`; passed to PHPMailer `SMTPSecure`, and an empty value (e.g. INI `false`) sets no explicit: missing default tls and false=plain stated; SMTPSecure mapping, empty-value meaning not documented //important//

### `docs/setup/upgrade/1.0.0-to-1.1.0.md` (7)
- `dbusername_elevated` and `dbpassword_elevated`: no default; when both are non-empty the migrate, seed, status, sync, un: missing which commands switch user and the both-non-empty condition not stated //important//
- Elevated credentials switch: commands using the `DatabaseConnection` trait call `db()->switchUser()` with `dbusername_el: missing Keys and fallback named; 'both non-empty' rule and which commands switch user missing //important//
- Elevated credentials: Trait `DatabaseConnection::setDatabaseConnection()` calls `switchUser()` only when both `dbusernam: missing Both keys must be non-empty; switchUser() via setDatabaseConnection() not stated //important//
- Commands using it: `db:migrate`, `db:status`, `db:sync`, `db:seed`, `db:unlock-migration` and `auth:migrate-hashing` cal: missing Which commands switch to the elevated user not listed //should-do//
- `2021-02-04_zubzet.sql`: Base schema with `CREATE TABLE IF NOT EXISTS` and `INSERT IGNORE`; also seeds `z_language` and : missing File only named as to-remove; schema content and seeds not described //extra-effort//
- `2025-11-06_user_permissions.sql`: Adds `z_user_permission`; `z_user` gains `active`, `updated`, a nullable `email` and : missing File only named as to-remove; z_user_permission and z_user changes not described //extra-effort//
- Bundled timeline: Application migrations touching `z_*` tables must be dated after the bundled migration creating them (: missing Mentions removing 2021-02-04_zubzet.sql; dating rule for app migrations touching z_* tables missing //important//

### `docs/setup/upgrade/1.1.0-to-1.2.0.md` (17)
- `app/Database/migrations/`: migration files read by `db:migrate` and `db:sync`, created with mode 0755 when missing.: missing Auto-creation of missing folder stated; mode 0755 and db:sync reading not stated //important//
- Default: `1000`; requests at least this slow are logged as a `SLOW_REQUEST` warning at shutdown.: missing only generic slow-request note; key, default 1000, SLOW_REQUEST warning missing //should-do//
- Default: `300`; queries taking at least this many milliseconds (`>=`, so `0` logs every query) are logged as a `SLOW_QUE: missing only generic slow-query note; key, default 300, >= semantics, SLOW_QUERY missing //should-do//
- Request and response: `setRequestResponse(new Request(Input::fromRequest()), new Response())` snapshots the superglobals: missing Reading input via request object documented; boot snapshot of superglobals and body not described //should-do//
- Input snapshot: `Input\State::fromRequest()` copies the superglobals once, so later writes to `$_GET`, `$_POST`, `$_COOK: missing Superglobals not used for input; one-time snapshot semantics not stated //should-do//
- `$req->input`: the raw `Input\State`, the only access path to `SERVER`, `SESSION`, `REQUEST` and `body`, which have no g: missing SERVER/SESSION/REQUEST/body have no getters; only shown as usage in upgrade steps //important//
- Directory side effect: listing migration or seed files creates `./app/Database/migrations` or `./app/Database/seed` with: missing Only migrations folder auto-creation mentioned; seed folder and mode 0755 missing //should-do//
- `organizationId`: `INT NULL DEFAULT NULL` placed after `id` with an index of the same name (since 2026-04-27); it points: missing INT NULL type, index and link to z_organization.id not stated //extra-effort//
- `groupId`: `INT NULL DEFAULT NULL` after `name` with no index; optional `z_role` group of the organization (since 2026-0: missing INT NULL DEFAULT NULL type and absence of index not stated //extra-effort//
- `active`: `TINYINT(1) NOT NULL DEFAULT 1` after `extended_seconds`, set to 0 on invalidation (since 2026-03-27).: missing Set to 0 on invalidation and TINYINT(1) DEFAULT 1 not stated //extra-effort//
- `size`: `INT NOT NULL`, widened to `BIGINT NOT NULL` on 2026-01-01 so large files fit.: missing Upgrade doc lacks 2026-01-01 date and NOT NULL; only INT to BIGINT //extra-effort//
- `categoryId` [deprecated]: `INT NOT NULL` column dropped on 2026-04-02.: missing Removal in 2026-04-02 stated; INT NOT NULL type not stated //optional//
- `2026-01-01_file_size.sql`: Changes `z_file.size` from `INT` to `BIGINT`.: missing Change described, but migration filename 2026-01-01_file_size.sql never named //extra-effort//
- `2026-05-04_organization_role.sql`: Adds `z_organization.groupId`; not re-runnable.: missing Not re-runnable not stated; generic note implies idempotent //extra-effort//
- `./app/Database/migrations`: Application migration folder, created (`mkdir` mode 0755, recursive) when missing and then : missing Auto-creation noted in bugfix; mkdir mode 0755 recursive and treated-as-empty missing //important//
- Hook: A shutdown function logs `SLOW_REQUEST` at `warning` when the request duration is at or above `logger_slow_request: missing logger_slow_request_ms key, default 1000 and warning level not stated //important//
- Hook: `Connection::exec` logs `SLOW_QUERY` at `warning` when the execute time is at or above `logger_slow_query_ms` (def: missing logger_slow_query_ms key, default 300 and warning level not stated //important//

### `docs/template-rendering-usages/sending-an-email.md` (10)
- `anonymous_language`: no default; language used by `sendEmailToUser()` when the user's language row has no value, and ma: missing language fetched from DB; anonymous_language setting and `en` fallback missing //should-do//
- Purpose: renders a view into a mail layout and sends it as HTML mail over SMTP, returning true or false.: missing Returns true/false and sends HTML mail via SMTP not stated //important//
- `$subject` as array: keyed by language with keys lowercased, the lowercased `$lang` entry is used with fallback to the `: missing Lowercased keys and fallback to 'en' entry not stated //should-do//
- `$document`: mail template path resolved like any view with `.php` optional, rendered with `render()` which silently fal: missing .php optional; missing template silently renders 500.php view not stated //should-do//
- `$options`: array merged into the template's `$opt` together with all render-injected keys.: missing Merge with render-injected keys not stated //should-do//
- Layout contract: mail layouts follow the page layout contract and print `$body($opt)` and optionally `$head($opt)`.: missing Only shown by example; optional $head($opt) and the contract not stated //should-do//
- SMTP transport: always PHPMailer in exception mode with `isSMTP()`, `SMTPAuth = true` and `SMTPDebug = 0`, never `mail(): missing PHPMailer, SMTPAuth always on, never mail()/sendmail not stated //should-do//
- Purpose: loads a stored user by id and calls `sendEmail()`, returning its result.: missing Returning sendEmail()'s result not stated //important//
- Address: the `email` column of the `z_user` row loaded with `model("z_user")->getUserById()`.: missing email column of z_user via getUserById not named //should-do//
- Language: the `value` of the `z_language` row for the user's `languageId`, else the `anonymous_language` setting, else `: missing z_language value lookup and anonymous_language fallback not stated //should-do//

### `docs/z-admin/login-as-another-user.md` (6)
- Behavior: creates a session via the `z_login` model, sets the `z_login_token` cookie and logs `USER_LOGGED_IN` (or `USER: missing z_login_token cookie and USER_LOGGED_IN / USER_LOGGED_IN_ANOTHER log events not named //important//
- Executing user: `$user_exec` defaults to `$userId`, and a differing value records who is logged in as someone else.: missing Default $user_exec = $userId when omitted not stated //should-do//
- `userId`, `userId_exec`: `INT NOT NULL` each; `userId_exec` is the executing user and differs from `userId` for login-as: missing Columns userId/userId_exec (INT NOT NULL) not named //extra-effort//
- `$execUserId`: `?int` real user behind a login-as session, equal to `$userId` for normal logins.: missing Property name $execUserId and equality to $userId for normal logins not stated //important//
- Purpose: Creates a login token for `$userId` and sends it as cookie; `$user_exec` is the executing user and defaults to : missing Token sent as cookie and $user_exec defaulting to $userId not stated //important//
- Access: Requires `admin.su` and is also reached by the `Login as` button of the edit form.: missing admin.su gating of /z/login_as and Login as button on edit form not stated //should-do//

### `docs/z-admin/usage.md` (20)
- `LoginController` and `ZController`: bundled login flow and admin panel controllers (documented in their own sections).: missing ZController/LoginController class names and bundled roles not stated //should-do//
- URL scheme: `/z/{action}/{param}` is served by convention routing and the first segment `z` resolves to class `ZControll: missing /z/{action}/{param} scheme and ZController class resolution not stated //important//
- `admin.panel`: Dashboard at `/z`.: missing Only listed by name; dashboard at /z gating not stated //important//
- `admin.user.add`: Add-user form and creation.: missing Only listed by name; add-user form and creation gating not stated //important//
- `admin.user.list`: User list at `/z/edit_user`.: missing Only listed by name; user list at /z/edit_user gating not stated //important//
- `admin.user.edit`: Open and save a single user.: missing Only listed by name; open and save of a single user not stated //important//
- `admin.su`: `/z/login_as/{userId}`.: missing Only listed by name; /z/login_as/{userId} gating not stated //important//
- `admin.roles.list`: Roles list and prerequisite of every `/z/roles` request.: missing Only listed by name; prerequisite of every /z/roles request not stated //important//
- `admin.roles.create`: POST action `create` on `/z/roles`.: missing Only listed by name; POST action create on /z/roles not stated //important//
- `admin.roles.edit`: Open and save a role and prerequisite of delete.: missing Only listed by name; open/save role and delete prerequisite not stated //important//
- `admin.roles.delete`: POST action `delete` on `/z/roles/{roleId}`.: missing Only listed by name; POST action delete on /z/roles/{roleId} not stated //important//
- Access and view: Requires `admin.panel` and renders `administration/dashboard.php`.: missing Dashboard view file and admin.panel requirement not tied together //should-do//
- Users and Roles cards: Edit User (`admin.user.edit`), Add User (`admin.user.add`), Roles (`admin.roles.list`) and Groups: missing Groups card missing; per-card permission gating not stated //should-do//
- Access: Requires `admin.user.add`.: missing admin.user.add only listed; form and creation gating not stated //should-do//
- User picker: Without an id it requires `admin.user.list` and renders `administration/user_select.php` with active users : missing admin.user.list gate, active-only picker and z/edit_user/{id} links not stated //should-do//
- Single user: Opening or saving an id additionally requires `admin.user.edit`.: missing Extra admin.user.edit requirement for open/save only listed by name //should-do//
- Form fields: `email`, a roles CED (select `role`), a `Login as` action button and a `User-Level Permissions` CED (text `: missing Email field, Login as button and user-level permissions CED not described //should-do//
- Base access: `admin.roles.list` is checked first for every request, including create and delete.: missing admin.roles.list checked first for every request incl. create and delete not stated //should-do//
- Edit access: Opening or saving a role needs `admin.roles.edit`.: missing admin.roles.edit only listed by name; open/save gating not stated //should-do//
- Delete: POST action `delete` additionally needs `admin.roles.delete` and only sets `active = 0` via `deactivateRole`, ke: missing admin.roles.delete only listed; soft deactivation keeping permissions and user links not stated //should-do//
