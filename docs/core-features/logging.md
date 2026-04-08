# Logging

## What is this?

ZubZet provides a built-in logging system powered by [Monolog](https://github.com/Seldaek/monolog). It allows you to record application events, errors, and diagnostic information either to a database table or to a stream (file or stderr). The framework automatically logs certain system events such as logins, page views, and REST errors.

Loggers are created lazily on first use and cached for the lifetime of the request, so there is no overhead for channels that are never written to.

## Disabling Logging

Logging is **enabled by default**. To disable it, set the following in `z_config/z_settings.ini`:

```ini
logger_enabled = false
```

When disabled, all `logger()` calls still work but write to a `NullHandler` — log messages are silently discarded without any errors.

## Configuration

All logger settings are configured in `z_config/z_settings.ini`.

| Key                 | Type    | Default        | Description                                                  |
| ------------------- | ------- | -------------- | ------------------------------------------------------------ |
| `logger_enabled`    | boolean | `true`         | Enables or disables the logging system entirely              |
| `logger_type`       | string  | `database`     | The logger backend to use: `database` or `stream`            |
| `logger_level`      | string  | `debug`        | Minimum log level to record (see levels below)               |
| `logger_stream_url` | string  | `php://stderr` | Stream target — only used when `logger_type = stream`        |

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

## Writing Log Entries

Use the global `logger()` helper function anywhere in your application.

```php
// Log to the app channel
logger()->info("User submitted a contact form", ["email" => $email]);

// Log to a named channel
logger("payments")->warning("Payment gateway timeout", ["orderId" => $id]);
```

`logger()` without arguments defaults to the `"app"` channel. Any string can be passed to create a named channel — each channel is cached after the first call.

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
```

The second parameter `$context` is an optional associative array with additional data to attach to the log record.

## Registering a Custom Logger

If you need full control over a logger — custom handlers, formatters, or processors — you can build a Monolog `Logger` instance manually and register it under a channel name using `LoggerFactory::register()`. Once registered, it is returned by `logger()` like any other channel.

```php
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use ZubZet\Framework\Logger\LoggerFactory;

$customLogger = new Logger("audit");
$customLogger->pushHandler(new RotatingFileHandler("/var/log/audit.log", 30));

LoggerFactory::register("audit", $customLogger);
```

```php
// Anywhere in your application
logger("audit")->info("User exported data", ["userId" => $id]);
```

If a logger with that name was already created by the framework, `register()` overwrites it in the cache.

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

If the database connection is not available at the time of logging, the entry is silently skipped.

#### `value` Column Structure

The `value` column contains a JSON-encoded object with the full log record and environment context:

```json
{
    "message": "User logged in",
    "context": { "userId": 42 },
    "level": 200,
    "level_name": "INFO",
    "channel": "zubzet",
    "datetime": "2026-04-08T12:00:00+00:00",
    "extra": {},
    "environment": {
        "userId": 42,
        "execUserId": 42,
        "source": "web"
    }
}
```

The `environment.source` field is either `"web"` or `"cli"` depending on how the request was triggered.

#### Value Normalization

Before JSON encoding, context values are automatically normalized to ensure safe serialization:

| PHP type                         | Stored as                                          |
| -------------------------------- | -------------------------------------------------- |
| `string`, `int`, `float`, `bool`, `null` | Passed through unchanged                   |
| `array`                          | Recursively normalized                             |
| `DateTimeInterface`              | ISO-8601 string (e.g. `2026-04-08T12:00:00+00:00`) |
| `JsonSerializable`               | Result of `jsonSerialize()`, then normalized       |
| Object with `__toString`         | String representation                              |
| Other objects                    | Fully qualified class name                         |
| Resource                         | `resource(stream)` style string                    |

If JSON encoding fails despite normalization, a fallback record containing only the message and the encoding error is stored instead.

---

### Stream Logger

Writes log records as JSON to a file or PHP stream, one record per line.

```ini
logger_type = stream
logger_stream_url = php://stderr
```

`logger_stream_url` accepts any valid PHP stream URL or file path:

```ini
logger_stream_url = php://stderr
logger_stream_url = php://stdout
logger_stream_url = /var/log/app.log
logger_stream_url = z_config/app.log
```

Each log entry is a single JSON object followed by a newline character. The environment context is appended automatically before formatting:

```json
{"message":"User logged in","context":{"userId":42},"level":200,"level_name":"INFO","channel":"zubzet","datetime":"2026-04-08 12:00:00","extra":{},"environment":{"userId":42,"execUserId":42,"source":"web"}}
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
