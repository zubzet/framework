# Models and the `App\` namespace

Framework docs: `vendor/zubzet/framework/docs/core-features/models.md`, `query-builder/index.md`, `query-builder/examples.md`.

## Contents

- Database models
- SQL formatting
- Non database models
- Validation and errors in models
- The `App\` namespace

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

- **Prefer plain SQL.** Written as a multi-line string in `$sql`, formatted as in "SQL formatting" below, parameters as `?` with the mysqli type string (`i`, `s`, `d`). Never interpolate values into SQL.
- **Use the query builder (`dbSelect`, `dbInsert`, `dbUpdate`) when the query is dynamic**: optional WHERE conditions, filters, `IN` lists, inserts or updates with many columns. Mixing both in one model is normal.
- One method per query. Names say what comes back: `get()`, `getByUser()`, `getRequests()`, `getContacts()`, `getUsageByUser()`; writes are verbs: `add()`, `accept()`, `setState()`, `setHidden()`, `linkFiles()`, `delete()`.
- Return plain arrays (`resultToArray()`, `resultToLine()`), `?array` for single rows, `string` or `int` for inserted keys. No entity classes, no hydration layers.
- Type hint parameters (`int $positionId`, `string $uuid`, `bool $hidden`). Cast booleans to `(int)` when binding.
- Every read filters `active = 1` (or `true` in the builder), in the WHERE and in the ON of every JOIN. Every write that "deletes" sets `active = 0`; there is no `DELETE` (`database.md`, "Deletion").
- Every query that returns a list has an explicit `ORDER BY`. A list in random order looks different on every load, and Cypress picks list items by position, which only holds when new rows land at a predictable place (`testing.md`, "Lists and positions").
- Ownership lives in the model: the query itself contains `userId = user()->userId` unless a `$canViewAny` flag is passed by a controller that already verified a support permission.
- Timestamps come from the database: `CURRENT_TIMESTAMP()` in SQL, or `date("Y-m-d H:i:s")` when a nullable "happened at" column is set from PHP.
- Shared join snippets can be private helper methods inside the model (`joinBriefing(&$query)`). Keep them private and tiny.
- No static methods on models: `model()` returns a singleton, so a static adds nothing and only loses `$this->exec()` and `$this->getModel()`. No methods that return view fragments or formatted strings. No "publicView()" style array shaping for API output inside a database model (that belongs to an API model or `App\`).
- Bind type `b` (blob) is not used: binary data is a file in S3 with a row pointing at it, not a column (`docs/core-features/models.md`).
- Infrastructure models (cache, queue, session helpers) use the `IsInternalModel` trait so their queries stay out of the debug bar's query tab (`docs/core-features/debug-bar.md`).
- `z_user`, `z_role`, `z_organization` and `z_logintoken` are reached through `User`, `Role`, `Organization` and `Session` (`docs/core-features/access-control.md`), not through SQL in a project model. Before adding a `company` or `tenant` table that only groups users, check whether `Organization` (one per user, optional linked permission group) already covers it.

## SQL formatting

The goal is a query someone else can read later without studying it. Many of these choices are debatable; they are fixed anyway, because the same shape in every query of every project is worth more than any single preference. Follow them instead of reopening the discussion. Where no rule fits, ask how someone else will read it.

```php
public function getWithUsers(string $name): array {
    $sql = "SELECT c.*, cu.`id` AS company_user_id, cu.`name` AS company_user_name
            FROM `company` AS c
            LEFT JOIN `company_user` AS cu
            ON c.`id` = cu.`companyId` AND cu.`active` = 1
            WHERE c.`active` = 1
            AND (c.`name` = ? OR c.`is_in_progress` = 0)
            ORDER BY c.`name` ASC, cu.`id` DESC
            LIMIT 50";
    $this->exec($sql, "s", $name);
    return $this->resultToArray();
}
```

**SELECT**, in the order the rules build on each other:

1. **One clause per line, keywords upper case and aligned under each other** (`SELECT`, `FROM`, `LEFT JOIN`, `ON`, `WHERE`, `AND`, `ORDER BY`, `LIMIT`). SQL fixes the clause order, so every query has the same shape and you see at a glance what belongs together. The same query on one line is hard to read.
2. **Every table and column name in backticks.** A column called `order` (a sort position), `limit` (an account limit) or `name` is realistic, and without backticks a keyword breaks the query (`FROM from`). Write them even when trying a query out in a SQL console, so they stay in your fingers.
3. **Each `AND` on its own line under `WHERE`. An `OR` goes in parentheses**, on its own line: without them `AND` and `OR` bind differently and the query returns something else (`active AND name = ? OR in_progress = 0` also returns inactive rows).
4. **`ORDER BY` always names the direction.** `ASC` is the default, write it anyway so the intent is explicit.
5. **No line grows long.** When the column list, the `ORDER BY` list or the conditions get long, continue on the next line, indented one level deeper, so the structure stays visible.
6. **Joins get short aliases with `AS`**: the first letter of each word of the table name (`company` is `c`, `company_user` is `cu`); be creative when two tables would collide, and never pick an alias that is itself a keyword (`on`, `or`, `in`, `as`, `is`). The alias itself needs no backticks, the columns behind it keep theirs. This is the one place a short name is right: an alias lives only inside this one query, and writing out `company_user` four times hides the structure you want to see.
7. **Join with `ON`, never through `WHERE`** (no comma joins filtered afterwards). The reason is one shape in every query; speed is at most a bonus. `ON` goes on the line after the `JOIN`; it is nearly always `id` first and the foreign key of the other table second (``c.`id` = cu.`companyId` ``), plus the `active` filter. Several joins repeat the pattern, each with its own `ON`.
8. **`LIMIT` comes last.**
9. **Subqueries** open with `IN (` and the inner query starts a new block, indented one level. **`UNION` is not indented**; it is rare, and indenting it does not fit the code.

```sql
WHERE s.`active` = 1
AND s.`name` IN (
    SELECT `name`
    FROM `company`
    WHERE `id` = ?
    AND `active` = 1
)
```

**INSERT and UPDATE** follow the same idea. A short `INSERT` stays on one line; once the column list is long, `VALUES` moves to its own line. An `UPDATE` has `SET` on its own line and the same `WHERE` as a SELECT; a long `SET` continues on the next lines. There is no `DELETE` (`database.md`, "Deletion").

```php
$sql = "INSERT INTO `company_user` (`companyId`, `userId`, `name`, `email`, `role`)
        VALUES (?, ?, ?, ?, ?)";

$sql = "UPDATE `company`
        SET `discount` = `discount` + 10
        WHERE `id` = ?
        AND `active` = 1";
```

**Why the variable is always `$sql`:** `$sql = "` is exactly eight characters, two indentation levels. Every following line starts two levels deeper than `$sql` and its keyword lines up with `SELECT`, `UPDATE` or `INSERT`, as in the examples. Editors also detect the string as SQL from its first keyword and highlight it.

**Exceptions** come only after the rule is understood. A tiny lookup may be one line, ``"SELECT * FROM `company` WHERE `id` = ? AND `active` = 1"``, and so may a short INSERT or an UPDATE of one field by id. Be honest about it: the moment an `ORDER BY` or another condition joins, split it into the full format. As a measure, a line fits in half the screen at a normal zoom, readable on a phone; there is no fixed character count.

## Non database models

Anything bigger than a few lines of orchestration is a model even if it never touches SQL: `ImportModel` (CSV parsing and upsert), `S3Model` (storage client), `QueueModel` (RabbitMQ), `IntegrationModel` (HTTP to another service), `NotificationModel`. They extend `z_model` too, so `model("Import")` works everywhere and `$this->getModel("S3")` works inside models.

A model may construct its client in `__construct($z_db, z_framework $booter)` after `parent::__construct(...)`, reading settings through `config("key")`. Not through `$booter->req->getBooterSettings()`, which older projects still use.

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
