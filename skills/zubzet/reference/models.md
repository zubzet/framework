# Models and the `App\` namespace

Framework docs: `vendor/zubzet/framework/docs/core-features/models.md`, `query-builder/index.md`, `query-builder/examples.md`. How a SQL statement is laid out (keywords, backticks, aliases, the `$sql` variable) is in [code-style/sql.md](../code-style/sql.md).

## Database models

```php
<?php

    class PositionModel extends z_model {

        public function get(string $uuid, bool $canViewAny = false): ?array {
            $query = $this->dbSelect([
                "position_briefing.*",
                "position.*",
                "position_briefing.id AS briefing_id",
            ], "position")
                ->leftJoin("position_briefing", [
                    "position.id = position_briefing.positionId",
                    "position_briefing.active" => true,
                ])
                ->where([
                    "position.uuid" => $uuid,
                    "position.active" => true,
                ]);

            if(!$canViewAny) {
                $query->where([
                    "position.userId" => user()->userId,
                    "position.is_hidden" => false,
                ]);
            }

            return $this->exec($query)->resultToLine();
        }

        public function setState(int $positionId, string $state) {
            $sql = "UPDATE `position`
                    SET `state` = ?
                    WHERE `id` = ?";
            $this->exec($sql, "si", $state, $positionId);
        }

        public function delete(int $positionId) {
            $sql = "UPDATE `position`
                    SET `active` = 0
                    WHERE `id` = ?";
            $this->exec($sql, "i", $positionId);
        }

    }

?>
```

Rules:

- **Prefer plain SQL for reads.** Written as a multi-line string in `$sql`, formatted as in [code-style/sql.md](../code-style/sql.md), parameters as `?` with the mysqli type string (`i`, `s`, `d`). Never interpolate values into SQL.
- **Use the query builder (`dbSelect`, `dbInsert`, `dbUpdate`) when the query is dynamic** (optional WHERE conditions, filters, `IN` lists) **and for wider writes**: an `INSERT` beyond two or three columns or an `UPDATE` beyond four or five is an array of column => value in the builder, not a type string with a dozen letters and a value list to keep in sync. Mixing both in one model is normal.
- **Group models in subfolders** once the flat list gets long: `app/Models/store/TransactionModel.php` is still `model("Transaction")`, the registry finds it by name. The old dot syntax `model("Store.Transaction")` is never written again and is migrated to the plain name when encountered.
- A large model may separate its method groups with a `/** Retrieval */`, `/** Payments */` style marker; when it needs several, a subfolder with two models is usually the better split.
- One method per query. Names say what comes back: `get()`, `getByUser()`, `getRequests()`, `getContacts()`, `getUsageByUser()`; writes are verbs: `add()`, `accept()`, `setState()`, `setHidden()`, `linkFiles()`, `delete()`.
- Return plain arrays (`resultToArray()`, `resultToLine()`), `?array` for single rows, `string` or `int` for inserted keys. No entity classes, no hydration layers.
- Cast booleans to `(int)` when binding; the parameter itself stays `bool $hidden` (type hints: `code-style/php.md`).
- **Bind types fail silently when they do not match.** An array bound as `i` is stored as `1`, a `json_encode` result bound as `i` as `0`, and nothing throws. Count the placeholders against the type string, and cast request input at the boundary (`(int) $req->getPost("id")`) or validate it as an integer field.
- **There is no transaction in a model.** `exec()` auto-commits every statement (a transaction API is planned in the framework). Put the invariant into the statement itself, `UPDATE ... SET stock = stock - ? WHERE id = ? AND stock >= ?`, and check the affected rows, instead of a read followed by a write. `executeMultiQuery` and `executeOrderQuery` are internal to migrations and seeds and are never called from a model.
- Every read filters `active = 1` (or `true` in the builder), in the WHERE and in the ON of every JOIN. Every write that "deletes" sets `active = 0`; there is no `DELETE` (`database.md`, "Deletion").
- Every query that returns a list has an explicit `ORDER BY`. A list in random order looks different on every load, and Cypress picks list items by position, which only holds when new rows land at a predictable place (`testing.md`, "Lists and positions").
- Ownership lives in the model: the query itself contains `userId = user()->userId` unless a `$canViewAny` flag is passed by a controller that already verified a support permission.
- Timestamps come from the database: `CURRENT_TIMESTAMP()` in SQL, or `date("Y-m-d H:i:s")` when a nullable "happened at" column is set from PHP.
- Shared join snippets can be private helper methods inside the model (`joinBriefing(&$query)`). Keep them private and tiny.
- No static methods on models: `model()` returns a singleton, so a static adds nothing and only loses `$this->exec()` and `$this->getModel()`. No methods that return view fragments or formatted strings. No "publicView()" style array shaping for API output inside a database model (that belongs to an API model or `App\`).
- Bind type `b` (blob) is not used: binary data is a file in S3 with a row pointing at it, not a column (`docs/core-features/models.md`).
- Infrastructure models (cache, queue, session helpers) use the `IsInternalModel` trait so their queries stay out of the debug bar's query tab (`docs/core-features/debug-bar.md`).
- `z_user`, `z_role`, `z_organization` and `z_logintoken` are reached through `User`, `Role`, `Organization` and `Session` (`docs/core-features/access-control.md`), not through SQL in a project model. Before adding a `company` or `tenant` table that only groups users, check whether `Organization` (one per user, optional linked permission group) already covers it.

## Non database models

Anything bigger than a few lines of orchestration is a model even if it never touches SQL: `ImportModel` (CSV parsing and upsert), `S3Model` (storage client), `QueueModel` (RabbitMQ), `IntegrationModel` (HTTP to another service), `NotificationModel`. They extend `z_model` too, so `model("Import")` works everywhere and `$this->getModel("S3")` works inside models.

A model may construct its client in `__construct($z_db, z_framework $booter)` after `parent::__construct(...)`, reading settings through `config("key")` (`framework-api.md`, "Models reading their settings").

**An integration model is the data layer in front of an external API.** Controllers and other models call `model("Integration")->request("POST", "segmentation", [...])` or a named method on it and get an array or `null` back; they never see the HTTP client. The client itself (Guzzle, an SDK) may be wrapped in `App\` when it carries real logic, but the model stays in the middle as the one place that knows the endpoint, the authentication and the error handling. `request()` returns `null` on failure so callers can test for it, and logs every call through `logger("integration-name")`: a channel per external API, carrying the request's trace id, so a call can be followed through every stack and microservice and expires with the log retention. Only for payment and other financial flows is a call additionally written to an `integration_event` table (`service`, `is_outgoing`, `name`, `raw_data`, `processed_at`, `error`), because those have to stay traceable long after the logs are gone.

## Validation and errors in models

- Models throw `\Exception` with a message that is safe to show when a process fails for a reason the user can fix (wrong CSV format, missing columns). The controller catches and returns it.
- Models do not validate request input. That is `FormField` in the controller.
- Models do not call `$res->...`. They have no access to the response and must not need it.

## The `App\` namespace

`composer.json` autoloads `App\` from `app/`. Use it for:

- **Enums with behavior**: a closed set the code branches on, with helpers for display. Only when both conditions hold. A list of strings nobody branches on is a VARCHAR column plus, at most, an array constant.

```php
<?php

    namespace App\Objects;

    enum Plan: string {
        case GOLD = "gold";
        case PRO = "pro";

        public function name(): string {
            return ucfirst($this->value);
        }

        public function icon(): string {
            return match($this) {
                self::GOLD => "star",
                self::PRO => "rocket",
            };
        }

        public static function fromName(string $name): ?self {
            return self::tryFrom(trim(strtolower($name)));
        }
    }

?>
```

- **Value objects and services with state** that a model should not carry (`App\Objects\S3Presign`).
- **Complex flows and backend processing** that span several models.

Not for: global helper functions, constants that belong to one model, anything that is really a model method. When in doubt, it is a model method.
