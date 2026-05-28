# Error Handling

ZubZet controls how errors and uncaught exceptions are surfaced through two settings in
`z_config/z_settings.ini`: **`showErrors`** (how much is promoted to an exception) and
**`execution_type`** (which environment you are in). Together they decide whether a visitor sees a
rich developer error page or nothing at all.

## `showErrors` levels

| Value | Constant | Behavior |
| ----- | -------- | -------- |
| `0` | `NONE` | PHP's built-in error display; nothing is promoted. |
| `1` | `EXCEPTIONS` | Uncaught exceptions are handled and displayed. |
| `2` | `ALL` | All errors and warnings are promoted to exceptions and handled. |

## The Whoops error page

In the **`test`** environment (`execution_type = test`), ZubZet registers
[Whoops](https://github.com/filp/whoops) to render unhandled exceptions as an interactive page with
the full stack trace, source snippets, and request context — a major step up from a plain error
message while developing.

!!! warning "Test environment only"
    The Whoops page is intentionally limited to `execution_type = test`. In production
    (`execution_type = prod`) it is never registered, so stack traces are not exposed to visitors.

### Clickable file links

Whoops can turn each stack frame into a link that opens the file in your editor. Two settings drive
this:

- **`development_editor`** — the editor scheme to use (default `vscode`).
- **`automated_host_working_directory`** — your project's path *on the host*, so links resolve
  correctly even though the code runs inside a container. The
  [`info:startup`](console-commands.md#infostartup) command sets this when run with
  `--pwd "$(pwd)"` (the dev stack's `npm run info` does this automatically).

### Sensitive data masking

Whoops automatically masks request values whose keys look sensitive (containing `pass`, `secret`,
`token`, `session`, `auth`, `key`, `credential`, …) across `$_GET`, `$_POST`, `$_COOKIE`,
`$_SESSION`, `$_SERVER`, and `$_ENV`, so credentials are not printed on the error page.

## Related

- [Debug Bar](debug-bar.md) — per-request queries, templates, and log records (also `test`-only).
- [Logging](logging.md) — persist errors and events to the database or a stream.
