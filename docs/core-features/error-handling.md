# Error Handling

ZubZet controls how errors and uncaught exceptions are surfaced through two settings in
`z_config/z_settings.ini`: **`showErrors`** (how much is promoted to an exception) and
**`execution_type`** (which environment you are in). Together they decide whether a visitor sees a
rich developer error page or nothing at all.

## `showErrors` levels

| Value | Constant | Behavior |
| ----- | -------- | -------- |
| `0` | `NONE` | `display_errors` and `display_startup_errors` are set to `0` and `error_reporting` to `0`; nothing is promoted and PHP errors are neither shown nor logged; an exception thrown by an action is caught and the router runs the `error/500` route. |
| `1` | `EXCEPTIONS` | `display_errors` and `display_startup_errors` are set to `1` and `error_reporting` to `E_ALL`; PHP errors are logged and left to PHP's own handler, uncaught exceptions are handled and displayed; an exception thrown by an action is rethrown. |
| `2` | `ALL` | Every reportable error and warning is logged and promoted to an `ErrorException`, which is rethrown like any action exception; `display_errors`, `display_startup_errors` and `error_reporting` are left untouched. |

`showErrors` is required and has no default; the allowed values are the constants of `BehaviorOption` (`BehaviorOption::isValidOption()` checks a value), and `setExceptionBehavior()` applies the configured one at boot (calling it with an integer overrides the setting for the rest of the request). The configured value may be written as a string (`"1"`). Anything else, such as an out-of-range integer (`3`) or a non-numeric value, is treated as `0` and does not throw. The setting itself has to exist: a missing `showErrors` key throws an `InvalidArgumentException` ("The attribute 'showErrors' does not exist in the attribute store (yet)") during startup; errors thrown by the configuration or the [maintenance gate](maintenance.md) happen before the handlers are installed, so Whoops and the logger do not see them. Only a direct call to `setExceptionBehavior()` with an explicit out-of-range integer throws `InvalidArgumentException` ("Invalid exception behavior option: X").

The router runs `error/404` when the controller file does not exist or when neither the action nor `action_fallback` exists. An exception thrown while including the controller file always runs `error/500`; exceptions thrown while constructing or running the action follow the table above; PHP `Error`s are never caught there and reach the global exception handler. The bundled `ErrorController` answers `action_403`, `action_404` and `action_500` with the matching status code and renders the `403`, `404` or `500` view in `layout/min_layout`; the three Blade views are identical except for the heading (`Oops! Page not for you`, `Oops! Page not found`, `Oops! This page seems to be broken`), the digits and the message (`It seems like you don't have the permissions to visit this page!`, `The page you requested couldn't be found!`, `Sorry, we messed up!`). To replace them, add an application `ErrorController.php` (it has to implement all three actions, otherwise a missing action keeps rerouting to the 404 page until the limit of 10 reroutes ends the request with "Error: Too many reroutes.") or application views named `403`, `404` and `500`.

## The Whoops error page

In the **`test`** environment (`execution_type = test`), ZubZet registers
[Whoops](https://github.com/filp/whoops) to render unhandled exceptions as an interactive page with
the full stack trace, source snippets, and request context — a major step up from a plain error
message while developing.

!!! warning "Test environment only"
    The Whoops page is intentionally limited to `execution_type = test`. In production
    (`execution_type = prod`) it is never registered, so stack traces are not exposed to visitors: uncaught exceptions are logged and rethrown to PHP's default handler.

### Clickable file links

Whoops can turn each stack frame into a link that opens the file in your editor. Two settings drive
this:

- **`development_editor`** — the editor URL scheme (default `vscode`), producing `<editor>://file/<host path><relative file>:<line>`.
- **`automated_host_working_directory`** — your project's path *on the host*, so links resolve
  correctly even though the code runs inside a container; without it no links are made. The
  [`info:startup`](console-commands.md#infostartup) command sets this when run with
  `--pwd "$(pwd)"` (the dev stack's `npm run info` does this automatically).

### Sensitive data masking

Whoops automatically [masks request values](../api/classes/ZubZet-Framework-ErrorHandling-WhoopsHandler.html) whose keys look sensitive (containing `pass`, `secret`,
`token`, `session`, `auth`, `access`, `key`, `credential`, `private`, `bearer`) across `$_GET`, `$_POST`, `$_COOKIE`,
`$_SESSION`, `$_SERVER`, and `$_ENV`, so credentials are not printed on the error page.

## Related

- [Debug Bar](debug-bar.md) — per-request queries, templates, and log records (also `test`-only).
- [Logging](logging.md) — persist errors and events to the database or a stream.
