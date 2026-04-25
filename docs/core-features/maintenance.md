# Maintenance Mode
A standalone gate that runs immediately after configuration loads, before the logger, exception handler, and database. When active it sends `503 Service Unavailable` with `Retry-After: 300` and exits without any shutdown handlers, DB queries or log writes. Safe to enable while running a live migration.

## Modes
| Value      | HTTP requests                | CLI commands |
| ---------- | ---------------------------- | ------------ |
| `disabled` | Pass through                 | Pass through |
| `soft`     | Blocked unless bypass cookie | Pass through |
| `enabled`  | Blocked                      | Pass through |
| `full`     | Blocked                      | Blocked      |

Set this mode in the settings file or as usual using an ENV variable.

```ini
maintenance_mode = enabled
```

Case-insensitive. Unknown values fall back to `disabled`.

## Bypass cookie
In `soft` mode, requests with a `maintenance` cookie pass through. **The value is not validated**, so any value bypasses. Admins can set it via the admin panel. It expires after 24 hours.

## CLI behavior
In `full` mode, CLI commands write `Service Unavailable` to stderr and exit with code `1`. Use this to pause cron jobs alongside HTTP traffic.

## Template
The HTTP body comes from the first usable file:

1. `app/Views/maintenance.html` Project override
2. (framework) `src/Maintenance/maintenance.html` Framework default

A template is skipped if empty or if it contains `<?php` (the gate uses `file_get_contents`, never `include`).

The template is only used for HTTP responses. CLI hits always receive the bare `Service Unavailable` string regardless of mode.

## Admin panel
`/z/maintenance` shows the current status and exposes a button to set the bypass cookie. Requires the `admin.maintenance` permission. Only reachable in `disabled` or `soft`-with-cookie; in `enabled` or `full` you must edit the INI directly to toggle off.
