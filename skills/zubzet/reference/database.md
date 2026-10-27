# Database: tables, migrations, seeds, deletion

Framework docs: `vendor/zubzet/framework/docs/core-features/migrations/index.md`, `migrations/examples.md`. How to write and format the SQL itself is in [models.md](models.md), "SQL formatting".

## Contents

- Table structure
- Naming tables and columns
- Migration template
- Migration or seed?
- Seeds
- Deletion

## Table structure

Every table, without exception and including pure mapping tables, has three columns:

- `id`, auto increment primary key: every row needs one, mapping rows too, and adding it later is a migration nobody wants.
- `active`, `TINYINT(1)` with default `1`: the soft delete flag. Nothing is ever hard deleted, so whatever someone breaks can be restored (see Deletion).
- `created`, `TIMESTAMP` with default `CURRENT_TIMESTAMP()`: the creation time is the one fact you can never find out afterwards, and storing it costs nothing.

The template below adds `uuid` and `updated` to these.

Because of `active`, every SELECT filters `active = 1`, and so does every JOIN, in its ON condition: ``LEFT JOIN `company_user` AS cu ON c.`id` = cu.`companyId` AND cu.`active` = 1``. Strictly the JOIN filter is not always needed, but write it every time so it is a habit and not a decision.

Further rules:

- **Keys go to the front**: `id`, `uuid`, then the foreign keys. Ownership keys (who owns the row, who it is about: `userId`, `companyId`) always come first. A foreign key that is really data, split out only for normalization (`colorId`), may sit with the data columns.
- **Index every foreign key column right away.** With 50 rows it makes no difference; it is a habit that pays off once the table grows.
- **`VARCHAR(255)` is the default for single line text**: long enough for any name, so nobody has to guess a length. Take less when the value is clearly shorter. Whatever the length, the `FormField` rule must match it (`->length(1, 255)`): in strict mode a longer value makes the INSERT fail and the row is not saved, and switching the database to silent truncation is worse. Check the length before the value reaches the database.
- **`TIMESTAMP` for points in time, and preferred for dates.** A start or end date is normally a `TIMESTAMP` too, so adding the time later needs no migration. `DATE` is allowed where a date is simply the better fit (a birth date, a public holiday). One hard limit: `TIMESTAMP` holds nothing before 1970, and before 2106 only on MariaDB 11.5 or newer (2038 elsewhere); in strict mode such a value fails the INSERT, in non strict mode it silently becomes the zero date.

## Naming tables and columns

- **Table names are singular**, snake_case: a table holds entities, and a row is one record. A table of jobs is `job`; likewise `company`, `company_user`, `segmentation`. Never `jobs` or `companies`: you fetch "a segmentation with its companyId", not a collection.
- **Columns are lowercase snake_case** (`name`, `company_size_color`). **Foreign keys are camelCase** `<table>Id` (`userId`, `projectId`, `companyId`). Looking at a table, the casing alone shows which columns point elsewhere and which are plain data.
- **Do not repeat the table name.** In `project` the columns are `name`, `date_start`, `date_end`, not `project_name` or `project_start`.
- **General word first, specific after**, so related columns sort next to each other and read as a group: `date_start` and `date_end`; `description`, `description_short`, `description_public`, `description_seo`. Not `short_description`. The type word is optional (`start` and `end` alone are fine, just as nobody writes `string_name`); the grouping is the point.
- Booleans are `TINYINT(1)` named `is_*` or a plain adjective (`active`, `uploaded`). Single events are nullable `TIMESTAMP` named `*_at` (`accepted_at`).
- Names like `order`, `limit`, `name` or `from` are SQL keywords. They still work as column names because every identifier is written in backticks ([models.md](models.md)).

## Migration template

File: `app/Database/migrations/YYYY-MM-DD_<name>.sql` (add `_1`, `_2` between date and name when several land on one day). Plain SQL. Each statement ends with `;`.

```sql
CREATE TABLE `position` (
    `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    `userId` INT NOT NULL,

    `accepted_at` TIMESTAMP NULL,
    `file_link` TEXT NULL,
    `file_linked_at` TIMESTAMP NULL,

    `state` VARCHAR(50) NOT NULL DEFAULT "Offen",
    `is_hidden` TINYINT(1) NOT NULL DEFAULT 0,
    `product` VARCHAR(30) NOT NULL,
    `name` VARCHAR(255) NOT NULL,

    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    INDEX(`uuid`, `active`),
    INDEX(`userId`)
);
```

Mandatory on every table, in this order: `id`, `uuid`, foreign keys, business columns grouped by blank lines, `active`, `updated`, `created`, then indexes on `uuid` (together with `active`, since every lookup filters it) and every FK column.

- Design the table, then put the plain `CREATE TABLE` in the file. No `ENGINE`, `CHARSET` or `COLLATE` clauses: take the database defaults.
- No `FOREIGN KEY` constraints, no `ENUM` columns, no table prefixes, no `DATETIME` where `TIMESTAMP` does the job, no JSON columns for things that should be rows, no named indexes.
- `TEXT` for free text, `VARCHAR(255)` for single line, the native `UUID` type for uuids (as the framework's own tables do), `VARCHAR(50)` for short states.
- The migrations folder is flat: one file per change, sorted by date, run top to bottom. Later changes are new files after the CREATE: `2026-05-21_contact_workspace_out.sql` with `ALTER TABLE ... ADD COLUMN ... AFTER ...`. Never edit a migration that has run anywhere.
- Migrations are how the schema travels: they run on production, and a colleague who pulls your change runs `db:migrate` and has the same database. Nobody exchanges database exports.
- **Numeric `id`s never leave the server.** They are for joins and foreign keys. Everything the browser sees carries the `uuid`: URLs (`/position/details/<uuid>`), `data-*` attributes, hidden form fields, `Z.Request.action` payloads, JSON. An authenticated page is still public markup, and a sequential id reveals counts and invites guessing. The model resolves a uuid to its row, and the ownership check lives in that query.
- Generate uuids in the database (`DEFAULT UUID_v7()`) or with `Symfony\Component\Uid\Uuid::v7()` when the code needs the value before the INSERT. Version 7 is time ordered, so inserts land at the end of the index instead of scattering across it; do not use `v4()` or MariaDB's `UUID()` in new code.
- **Never add a column with a function default to an existing table in one step** (`ALTER TABLE ... ADD COLUMN uuid ... DEFAULT UUID_v7()`). Each Galera node runs the ALTER itself and evaluates the function for the existing rows on its own, so the nodes end up with different values for the same row. Add the column nullable without a default, backfill with an `UPDATE` (data changes replicate as rows), then set `NOT NULL` and the default, as the framework's own migration does (`src/IncludedComponents/database/Migration/2026-09-08_uuid.sql`):

```sql
ALTER TABLE `position`
    ADD COLUMN `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `position` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `position`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX (`uuid`, `active`);
```
- PHP migrations exist for changes that depend on data or environment. Plain SQL stays the default (`docs/core-features/migrations/index.md`, `migrations/examples.md`).
- Run with `php zubzet db:migrate`, reset locally with `npm run seed`. The production rollout sequence and the lock commands are in `docs/core-features/migrations/index.md`; follow it instead of improvising.

## Migration or seed?

Ask one question: does production need this row?

- **Yes: migration.** The structure, plus the data the application cannot work without in any environment. A lookup table such as `offer_country` (the countries an offer is available in, with their code and a `restricted` flag) is useless empty; nobody could select anything. Its rows ship in the migration next to the `CREATE TABLE`.
- **No: seed.** Demo and test data, users, tokens, rows in every state a test checks. Production never sees it (`db:seed` drops the database).

## Seeds

`app/Database/seed/<area>/<table>.sql`: a folder per area (`accounts/`, `customer/`), one file per table inside, explicit ids, fixed uuids that encode the id (`'00000000-0000-7000-8000-000000000001'`, valid for the native `UUID` type and readable in a test), timestamps as `CURRENT_TIMESTAMP`, and one row per state the UI distinguishes (new, briefed, accepted, uploaded, hidden...). Comments in the seed say what a row is for. Accounts, roles, permissions and login tokens live in `seed/accounts/` (`0_permissions.sql`, `1_users.sql`, as in the `zubzet/zubzet` skeleton).

Seed files run in path order (collected recursively and sorted, `src/IncludedComponents/models/z_migrationModel.php`). When one file must run before another, force it with a `0_`, `1_` prefix, as `accounts/` does.

A seed builds the exact state the tests need: several inserts, or an insert followed by an update, whatever produces that state. This is where tests get their data from, not from other tests (see [testing.md](testing.md), "Independence and test data").

`db:seed` drops and recreates the whole database, so it never runs against production. Bulk demo data that only development needs goes into a subfolder such as `seed/Environments/Dev/`, which the seed command can exclude or include by folder (`docs/core-features/console-commands.md`).

**Space out ids per feature.** The first feature uses ids 1 to n. The next feature that needs its own rows in the same table starts at the next round hundred (100, 200, ...), with uuids ending in `100` and so on. Two features landing at the same time then never fight over the same ids, and a seed file stays readable because each block belongs to one feature:

```sql
-- Positions for the customer flow (position.cy.js)
INSERT INTO `position` (`id`, `uuid`, `userId`, `product`, `name`) VALUES
(1, '00000000-0000-7000-8000-000000000001', 3, 'Gold', 'Position Gold Neu'),
(2, '00000000-0000-7000-8000-000000000002', 3, 'Gold', 'Position Gold Briefing');

-- Positions for the support filter (position_admin.cy.js)
INSERT INTO `position` (`id`, `uuid`, `userId`, `product`, `name`) VALUES
(100, '00000000-0000-7000-8000-000000000100', 4, 'Platinum', 'Position Hidden'),
(101, '00000000-0000-7000-8000-000000000101', 4, 'Pro', 'Position Pro Uploaded');
```

Never renumber existing seed rows when adding a feature; specs reference the ids and the list positions.

## Deletion

Soft delete everywhere, project mapping tables included. A delete is an UPDATE that sets `active = 0`; every read and every JOIN filters `active = 1`. A stack contains no `DELETE` statement, so any mistake can be rolled back by setting `active` back to `1`. The only exceptions: an explicit erasure request, and framework tables such as `z_user_role` that the framework's objects manage (`docs/core-features/access-control.md`). CED uses `active` to mark removed children; the table must have the column.
