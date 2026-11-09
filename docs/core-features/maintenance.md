# Maintenance Mode
A standalone gate that runs immediately after configuration loads, before the logger, exception handler, asset proxy, routing, and database. When active it sends `503 Service Unavailable` (`Content-Type: text/html; charset=UTF-8`, `Retry-After: 300`, always the HTML page, even for JSON, REST and asset-proxy requests) and exits without any shutdown handlers, DB queries or log writes. Safe to enable while running a live migration.

## Modes
| Value      | HTTP requests                | CLI commands |
| ---------- | ---------------------------- | ------------ |
| `disabled` | Pass through                 | Pass through |
| `soft`     | Blocked unless bypass cookie | Pass through |
| `enabled`  | Blocked                      | Pass through |
| `full`     | Blocked                      | Blocked      |

Set this mode in the [settings file](configuration.md) or, with `allow_env_config`, using an ENV variable (`CONFIG_MAINTENANCE_MODE`). It is read on every request, so editing it takes effect immediately. There is no CLI command or admin toggle for it.

```ini
maintenance_mode = enabled
```

Defaults to `disabled`. Case-insensitive. Unknown values fall back to `disabled`, including the INI word `off`, which normalises to an empty string.

## Bypass cookie
In `soft` mode, requests with a `maintenance` cookie pass through. **Only the presence of the cookie key is checked** (`MaintenanceHandler::checkBypassCookie()`), so any value bypasses, even an empty one. Admins can set it via the [admin panel](../z-admin/usage.md), which sends the value `true` for the `rootFolder` path with `SameSite=Lax` (plus `Secure` when `host` starts with `https://`, never `HttpOnly`). It expires after 24 hours. The page is unreachable while the gate blocks, so set the cookie before switching to `soft`.

## CLI behavior
In `full` mode, CLI commands write `Service Unavailable` to stderr and exit with code `1`. Use this to pause cron jobs alongside HTTP traffic.

## Template
The HTTP body comes from the first usable file:

1. `app/Views/maintenance.html` Project override
2. (framework) `src/Maintenance/maintenance.html` Framework default

The bundled page (title `Maintenance`, heading `We'll be back soon`) says the website is undergoing maintenance, asks to check back later, hints to contact the web administrator and reloads itself every 5 minutes. A template is skipped if empty or if it contains `<?php` (the gate uses `file_get_contents`, never `include`).

The template is only used for HTTP responses. CLI hits always receive the bare `Service Unavailable` string regardless of mode.

## Admin panel
`/z/maintenance` shows the current status and exposes a button to set the bypass cookie. Requires the `admin.maintenance` [permission](permission-system.md). Only reachable in `disabled` or `soft`-with-cookie; in `enabled` or `full` you must edit the INI directly to toggle off.
