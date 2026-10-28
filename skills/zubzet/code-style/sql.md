# SQL style

Applies to SQL in model strings, migrations and seeds. General rules are in [index.md](index.md); the `$sql` variable and the surrounding PHP follow [php.md](php.md).

The goal is a query someone else can read later without studying it. Where no rule fits, ask how someone else will read it.

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
3. **Each `AND` on its own line under `WHERE`. An `OR` goes in parentheses**, on its own line, because `AND` binds tighter and the query would silently return something else. A short `OR` stays inside one line of parentheses; a long one opens and closes them on their own lines, each operand on a line led by `OR`:

```sql
WHERE `active` = 1
AND (
    `employerId` IS NULL
    OR `employerId` = ?
)
```
4. **`ORDER BY` always names the direction.** `ASC` is the default, write it anyway so the intent is explicit.
5. **No line grows long.** Up to about three columns stay on the `SELECT` line. Beyond that, `SELECT` stands alone and every column gets its own line, indented one level deeper; the same for a long `ORDER BY` list or a long condition:

```sql
SELECT
    ed.`cta`,
    ed.`color`,
    ejt.`file` AS `template`,
    ejt.`id` AS `template_id`
FROM `employer_design` AS ed
```
6. **Every table in a query with joins gets a short alias with `AS`**, the main table included: the first letter of each word (`company` is `c`, `company_user` is `cu`), never a keyword (`on`, `or`, `in`, `as`, `is`). Columns are always written through the alias (``c.`id` ``); the alias needs no backticks, the columns keep theirs. This is the one place a short name is right: it lives inside this one query, and writing out `company_user` four times hides the structure.
7. **`LEFT JOIN` is the default join, with `ON`, never through `WHERE`** (no comma joins filtered afterwards). An inner `JOIN` only where the query must drop rows without a match. `ON` goes on the line after the `JOIN`; it is nearly always `id` first and the foreign key of the other table second (``c.`id` = cu.`companyId` ``), plus the `active` filter. Several joins repeat the pattern, each with its own `ON`. **Same-named columns of two joined tables overwrite each other in the result row** (mysqli builds a plain associative array, so `cu.id` replaces `c.id` when both are selected): select the second table's column under an alias, ``cu.`id` AS `company_user_id` ``, and never `SELECT *` across a join whose tables share column names.
8. **`LIMIT` comes last.**
9. **Column aliases are snake_case in backticks**, like a column, and written with `AS` (nearly always; a bare alias is legal but reads like a typo): ``AS `account_email` ``, ``AS `template_id` ``. A count is always ``COUNT(*) AS `CNT` ``, read as `$this->resultToLine()["CNT"]`.
10. **Subqueries** open with `IN (`, or with `FROM (` and `LEFT JOIN (` for a derived table, and the inner query starts a new block, indented one level; the closing `)` stands at the indentation of the opening keyword, followed by `AS alias` for a derived table. **`UNION` is not indented**; it is rare, and indenting it does not fit the code. A `-- comment` line inside a long query is fine where a step is not obvious.

```sql
WHERE s.`active` = 1
AND s.`name` IN (
    SELECT `name`
    FROM `company`
    WHERE `id` = ?
    AND `active` = 1
)
```

**INSERT and UPDATE** follow the same idea. `INSERT INTO` is followed by the table name and the column list without a space between them; a short `INSERT` stays on one line, otherwise `VALUES` moves to its own line. An `UPDATE` has `SET` on its own line and the same `WHERE` as a SELECT; with several columns `SET` stands alone and each assignment gets its own line, indented one level. There is no `DELETE` (`database.md`, "Deletion").

```php
$sql = "INSERT INTO `company_user`(`companyId`, `userId`)
        VALUES (?, ?)";

$sql = "UPDATE `segmentation`
        SET
            `is_in_progress` = 0,
            `prompt_positive` = ?,
            `prompt_negative` = ?
        WHERE `id` = ?";
```

Raw SQL is preferred for reads. For writes the type string and the value list grow with every column, so **an `INSERT` with more than two or three columns and an `UPDATE` with more than four or five use the query builder** (`dbInsert`, `dbUpdate` with an array of column => value, `models.md`), which is the cleaner syntax there.

**Why the variable is always `$sql`:** `$sql = "` is exactly eight characters, two indentation levels. Every following line starts two levels deeper than `$sql` and its keyword lines up with `SELECT`, `UPDATE` or `INSERT`, as in the examples. Editors also detect the string as SQL from its first keyword and highlight it.

**Parameters** are `?` placeholders with the mysqli type string (`i`, `s`, `d`); values are never interpolated into the string (`models.md`).

**Exceptions** come only after the rule is understood. A tiny lookup may be one line, ``"SELECT * FROM `company` WHERE `id` = ? AND `active` = 1"``, and so may a short INSERT or an UPDATE of one field by id. Be honest about it: the moment an `ORDER BY` or another condition joins, split it into the full format (the measure is the one from `index.md`: half the screen).

**Migrations and seeds** are plain SQL files with the same keyword casing and backticks. `CREATE TABLE` lists one column per line, indented one level, keys first, then blank lines between column groups (see the template in `database.md`). Each statement ends with `;`.

## Naming tables and columns

- **Table names are singular**, snake_case: a table holds entities, and a row is one record. A table of jobs is `job`; likewise `company`, `company_user`, `segmentation`. Never `jobs` or `companies`: you fetch "a segmentation with its companyId", not a collection.
- **Related tables share their first word**, so they sort and read as a group: `order`, `order_item`, `order_feedback`; `company`, `company_user`. The same general-word-first rule as for columns.
- **Never a `z_` prefix** on a project table. It is reserved for the framework's own tables (`z_user`, `z_role`, `z_logintoken`), and a project table with it would read as framework internals.
- **Columns are lowercase snake_case** (`name`, `company_size_color`). **Foreign keys are camelCase** `<table>Id` (`userId`, `projectId`, `companyId`). Looking at a table, the casing alone shows which columns point elsewhere and which are plain data.
- **Do not repeat the table name.** In `project` the columns are `name`, `date_start`, `date_end`, not `project_name` or `project_start`.
- **General word first, specific after**, so related columns sort next to each other and read as a group: `date_start` and `date_end`; `description`, `description_short`, `description_public`, `description_seo`. Not `short_description`. The type word is optional (`start` and `end` alone are fine, just as nobody writes `string_name`); the grouping is the point.
- **Prefer a nullable `TIMESTAMP` named `*_at` over a boolean.** `newsletter_subscribed_at`, `accepted_at`, `file_linked_at`: `NULL` means no, a value means yes and tells when, and that is information nobody can recover later. A real boolean (`TINYINT(1)`) stays for the rare state that has no moment attached, named `is_*`, `has_*` or a plain adjective (`is_hidden`, `has_newsletter`, `active`). The base columns `created` and `updated` are legacy names and the only `NOT NULL` timestamps without `_at`.
- Names like `order`, `limit`, `name` or `from` are SQL keywords. They still work as column names because every identifier is written in backticks.
