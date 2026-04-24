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