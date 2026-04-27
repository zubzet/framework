# Debug Bar

## What is this?

ZubZet ships with the [PHP Debug Bar](https://github.com/php-debugbar/php-debugbar) preconfigured for the development environment. It overlays a small bar at the bottom of every rendered page and surfaces what happened during the request: SQL queries, rendered templates and parameters, log records with their full context, request data, timings, and memory usage.

The bar is always available in the `test` environment and never rendered anywhere else. SQL, parameter values, and log context can contain sensitive data, so the gate is strict by design.

## Enabling

The debug bar is gated on `execution_type` in `z_config/z_settings.ini`:

```ini
execution_type = test
```

If `execution_type` is anything other than `test` (including unset), the bar is not bootstrapped, no assets are emitted, and no collectors run. There is no way to enable it for production.

The bar relies on the layout calling the body essentials. The default layout already does this. If you ship a custom layout and want the bar visible there too, include the body essentials inside your `<body>`:

```php
<?php $opt["layout_essentials_body"]($opt); ?>
```

## What you see

### Queries

Every SQL statement that runs through `Connection::exec()` (or `Model::exec()`) is captured with its placeholders interpolated, the bound values, the duration, and the row count. Click a query to open a parameter table and copy the statement to the clipboard.

Bound values are rendered as single quoted SQL literals so the displayed query can be pasted into a SQL client and executed as is.

```sql
INSERT INTO `model_test_insert` (`value`)
VALUES ('TestData')
```

### Templates

Every view passed to `$res->render(...)` is captured as a row in the templates tab, together with the layout name and the original options array.

```
core/render (layout: layout/default_layout.php)
```

Click the row to expand the params table. Values are formatted by the framework's data formatter, so arrays and objects are shown in a readable shape.

### Monolog

Every log record produced through `logger()` shows up as a row on the monolog tab, including the channel, level, message, full context, and processor extras (`traceId`, `file`, `line`, `class`, `function`).

The summary line is the trace id, channel, level, and message. Click the row to expand a key and value table with `context.*` and `extra.*` entries.

The tab uses the standard messages widget and includes a search input. Searching matches against the channel, level, message, and every context and extra value, so you can quickly filter to a specific trace id, view path, or user id.

## Hiding framework queries

The framework runs many queries on its own behalf for authentication, sessions, permissions, migrations, and similar concerns. By default these are hidden so the queries tab only shows queries from your own application.

```ini
debugbar_hide_internal_queries = true
```

Set it to `false` to see every query, including the ones the framework runs internally.

| Key                              | Type    | Default | Description                                                                 |
| -------------------------------- | ------- | ------- | --------------------------------------------------------------------------- |
| `execution_type`                 | string  | `prod`  | Must be `test` for the debug bar to render                                  |
| `debugbar_hide_internal_queries` | boolean | `true`  | Hide queries issued by models flagged with `IsInternalModel` from the bar |

## Marking your own models as internal

If you have models that you consider infrastructure rather than application logic, you can opt them into the same filter. Use the `IsInternalModel` trait on the model class:

```php
use ZubZet\Framework\Database\IsInternalModel;

class CacheModel extends z_model {
    use IsInternalModel;

    public function purge() {
        $this->exec("DELETE FROM `cache_entries`");
    }
}
```

Queries issued through this model are filtered out of the queries tab whenever `debugbar_hide_internal_queries = true`. Direct `db()->exec(...)` calls that bypass the model layer are always shown, regardless of the setting.

## Adding your own log context

The monolog tab automatically renders the context array you pass to `logger()` and any extras added by processors. Anything you add is searchable.

```php
logger("orders")->info("Order placed", [
    "orderId" => $order->id,
    "userId"  => $user->id,
    "total"   => $order->total,
]);
```

In the bar this expands into rows for `context.orderId`, `context.userId`, `context.total`, alongside the framework's own `extra.traceId`, `extra.file`, and friends.

See the [Logging documentation](logging) for details on channels, processors, and custom loggers.

## Example

```ini
# z_config/z_settings.ini
execution_type = test
debugbar_hide_internal_queries = true
```

```php
public function action_show(Request $req, Response $res) {
    $order = model("order")->find($req->getParameters(0));

    logger("orders")->info("Order opened", ["orderId" => $order["id"]]);

    return $res->render("orders/show", ["order" => $order]);
}
```

When you visit the page the debug bar will show:

* one query in the queries tab with the interpolated `WHERE id = '...'` clause
* one render in the templates tab named `orders/show (layout: layout/default_layout.php)` with the `order` array as a parameter
* one monolog row on the `orders` channel containing the `context.orderId` and the framework's `extra.traceId`
