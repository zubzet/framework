# Logging

## What is this?

ZubZet provides a built-in logging system powered by [Monolog](https://github.com/Seldaek/monolog). It allows you to record application events, errors, and diagnostic information either to a database table or to a stream (file or stderr). The framework automatically logs certain system events such as logins, page views, and REST errors. Which of them are stored depends on [`logger_level`](#log-levels).

Loggers are created lazily on first use and cached for the lifetime of the request, so there is no overhead for channels that are never written to.

## Disabling Logging

Logging is **enabled by default**. To disable it, set the following in `z_config/z_settings.ini`:

```ini
logger_enabled = false
```

When disabled, all `logger()` calls still work but write to a `NullHandler` — log messages are silently discarded without any errors.

## Configuration

All logger settings are configured in `z_config/z_settings.ini`.

| Key                 | Type    | Default        | Description                                                    |
| ------------------- | ------- | -------------- | -------------------------------------------------------------- |
| `logger_enabled`    | boolean | `true`         | Enables or disables the logging system entirely                |
| `logger_type`       | string  | `database`     | The logger backend, `database` or `stream` (case-sensitive); any other value throws an `InvalidArgumentException` |
| `logger_level`      | string  | `notice`       | Minimum log level to record: a name (case-insensitive) or number, see below; an unknown name throws an `InvalidArgumentException` |
| `logger_stream_url` | string  | `php://stderr` | Stream target — only used when `logger_type = stream`          |
| `logger_slow_request_ms` | number | `1000`    | Requests at least this slow (`>=`) are logged as `SLOW_REQUEST` at shutdown (not after an uncaught exception; console commands count too); a negative or non-numeric value disables it |
| `logger_slow_query_ms`   | number | `300`     | `exec()` queries (so `execQuery()`, not `executeMultiQuery()`) taking at least this many milliseconds (`>=`, so `0` logs every query; measured from prepare to execute, retries included, result fetch excluded) are logged as `SLOW_QUERY`; an INI file cannot disable it, so use a high threshold |

### Log Levels

Log levels follow the [Monolog/PSR-3 standard](https://www.php-fig.org/psr/psr-3/). Set `logger_level` to a level name — only records at or above that level are stored.

| `logger_level` value | Description                         |
| -------------------- | ----------------------------------- |
| `debug`              | Detailed diagnostic information     |
| `info`               | Normal operational events           |
| `notice`             | Significant but expected events     |
| `warning`            | Exceptional but non-critical events |
| `error`              | Runtime errors                      |
| `critical`           | Critical conditions                 |
| `alert`              | Action must be taken immediately    |
| `emergency`          | System is unusable                  |

The framework logs its own events on the `zubzet` channel (`Logger::ZUBZET`) at different levels. The event names are the constants of `ZubZet\Framework\Logger\LogEventType` and become the record `message`. With the default `notice`, the `info` events are dropped by the handler and not stored; set `logger_level = info` to record them.

| Level     | Framework events                                                                                                                                                      |
| --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `info`    | `RENDER` (every rendered view, mail renders included; context `location`, `view`, `viewName`, `layout`, `layoutName`), `USER_LOGGED_IN`, `USER_LOGGED_IN_ANOTHER`, `USER_LOGGED_OUT`, `USER_CREATED`, `ACCOUNT_UPDATED`, `PASSWORD_RESET_REQUESTED`, `PASSWORD_RESET` |
| `warning` | `SLOW_REQUEST` (`duration_ms`, `uri`), `SLOW_QUERY` (`duration_ms`, `query` without bound values), `REST_ERROR` (`code`, `message`), `ACCOUNT_LOGIN_RATE_LIMITED`                                                                                              |
| `error`   | `EXCEPTION` (`class`, `message`, `file`, `line`, `trace`)                                                                                                                                                           |

PHP errors are logged as `ERROR` (`error`), `PARSE` (`critical`), `WARNING` (`warning`), `DEPRECATION`/`NOTICE` (`notice`) or `STRICT` (`debug`), depending on their severity, with the context `message`, `file` and `line`. Failed password attempts, verification success, logouts of anonymous requests and admin actions (roles, permissions, database browsing) write no event, and `sendEmail()` failures are echoed instead of logged. `@`-suppressed errors and fatal errors are not logged.

## Writing Log Entries

Use the global `logger()` [helper function](global-helper-functions.md) anywhere in your application.

```php
// Log to the app channel
logger()->info("User submitted a contact form", ["email" => $email]);

// Log to a named channel
logger("payments")->warning("Payment gateway timeout", ["orderId" => $id]);
```

`logger()` without arguments defaults to the `"app"` channel (`Logger::APP`). Any string can be passed to create a named channel — each channel is cached after the first call.

### Available Methods

```php
logger()->debug("message", $context);
logger()->info("message", $context);
logger()->notice("message", $context);
logger()->warning("message", $context);
logger()->error("message", $context);
logger()->critical("message", $context);
logger()->alert("message", $context);
logger()->emergency("message", $context);
logger()->log($level, "message", $context);
```

The second parameter `$context` is an optional associative array with additional data to attach to the log record. PSR-3 `{placeholder}` interpolation in the message is not active.

Keys that should accompany every later record of a channel go into `extra` instead, via `logger()->contextAdd([...])`, `contextMergeFrom($name = "app")` (the source channel wins on shared keys; an empty name or a channel that was never created throws), `contextInspect(fn($context) => ...)` (the return value replaces the context) and `contextClear()`. All of them return the logger and can be chained, and a key named `traceId`, `file` or `line` overrides the built-in value.

Every record carries a 32-character hex `extra.traceId`, generated on first use and shared by the whole request. The framework reads no incoming trace header, so propagate one with `Logger::setTraceId($id)` (not validated, only later records carry it); `Logger::getTraceId()` returns it.

## Registering a Custom Logger

If you need full control over a logger — [custom handlers, formatters, or processors](https://github.com/Seldaek/monolog/blob/main/doc/02-handlers-formatters-processors.md) — you can build a `ZubZet\Framework\Logger\Logger` instance (it extends the Monolog `Logger`) manually and [register it under a channel name](../api/classes/ZubZet-Framework-Logger-LoggerFactory.html#method_register) using `LoggerFactory::register()`. Once registered, it is returned by `logger()` like any other channel, but it gets none of the `logger_*` handler and processor setup (no `traceId` or call-site `extra` unless you add a processor).

```php
use Monolog\Handler\RotatingFileHandler;
use ZubZet\Framework\Logger\Logger;
use ZubZet\Framework\Logger\LoggerFactory;

$customLogger = new Logger("audit");
$customLogger->pushHandler(new RotatingFileHandler("/var/log/audit.log", 30));

LoggerFactory::register("audit", $customLogger);
```

```php
// Anywhere in your application
logger("audit")->info("User exported data", ["userId" => $id]);
```

If a logger with that name was already created by the framework, `register()` overwrites it in the cache. `logger()` builds channels from the `logger_*` settings via `LoggerFactory::getOrCreateLogger()`, while `LoggerFactory::getLogger($name)` only returns a cached channel or `null`.

The parameter is typed `Logger|LoggerInterface`, but `register()` and `logger()` are declared to return the ZubZet `Logger`. A plain Monolog `Logger` or any other PSR-3 logger is therefore accepted and cached, yet `register()` and every later `logger()` call for that name throw a `TypeError` on return. Always register a `ZubZet\Framework\Logger\Logger`.

## Logger Types

### Database Logger

Stores log records in the `z_interaction_log` database table. This is the default logger type.

```ini
logger_type = database
```

Each log entry is stored as a row with the following columns:

| Column        | Type       | Description                                               |
| ------------- | ---------- | --------------------------------------------------------- |
| `id`          | INT        | Auto-incrementing primary key                             |
| `text`        | MEDIUMTEXT | The plain log message                                     |
| `value`       | MEDIUMTEXT | Full log record as a JSON string (see below)              |
| `userId`      | INT        | ID of the currently logged-in user (nullable)             |
| `userId_exec` | INT        | ID of the executing user when using "login as" (nullable) |
| `created`     | TIMESTAMP  | Timestamp of the log entry                                |

If the database connection is not available or the insert fails (for example a missing table mid-migration), the entry is silently skipped. Nothing purges `z_interaction_log`, so delete old rows yourself.

#### `value` Column Structure

The `value` column contains the JSON-encoded Monolog record. The processor adds `traceId`, `file`, `line`, `class` and `function` to `extra` (omitted below), and the environment keys `userId`, `execUserId` and `source` (`"web"` or `"cli"`) are appended at write time:

```json
{
    "message": "User logged in",
    "context": { "userId": 42 },
    "level": 200,
    "level_name": "INFO",
    "channel": "zubzet",
    "datetime": { "date": "2026-04-08 12:00:00.000000", "timezone_type": 3, "timezone": "UTC" },
    "extra": {
        "userId": 42,
        "execUserId": 42,
        "source": "web"
    }
}
```

Context values are not normalized. The record is encoded with `JSON_UNESCAPED_UNICODE`, `JSON_UNESCAPED_SLASHES`, `JSON_INVALID_UTF8_SUBSTITUTE` and `JSON_THROW_ON_ERROR`; if encoding fails, a fallback record containing only the message and the encoding error is stored instead.

---

### Stream Logger

Writes log records as JSON to a file or PHP stream, one record per line.

```ini
logger_type = stream
logger_stream_url = php://stderr
```

`logger_stream_url` accepts any valid [PHP stream URL](https://www.php.net/manual/en/wrappers.php) or file path (relative paths resolve against the working directory):

```ini
logger_stream_url = php://stderr
logger_stream_url = php://stdout
logger_stream_url = /var/log/app.log
logger_stream_url = z_config/app.log
```

Each log entry is a single JSON object followed by a newline character (an empty `context` or `extra` is written as `{}`). Missing directories are created and there is no rotation; the `php://stderr` default ends up in the PHP or webserver error log. The environment keys are appended to `extra` automatically before formatting:

```json
{"message":"User logged in","context":{"userId":42},"level":200,"level_name":"INFO","channel":"zubzet","datetime":"2026-04-08 12:00:00","extra":{"userId":42,"execUserId":42,"source":"web"}}
``` 

## Example

```ini
# z_config/z_settings.ini
logger_type = database
logger_level = info
```

```php
public function action_order(Request $req, Response $res) {
    $orderId = model("order")->createOrder($req->getPost("items"));

    logger()->info("Order placed", ["orderId" => $orderId]);

    return $res->success(["orderId" => $orderId]);
}
```
