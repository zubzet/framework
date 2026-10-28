# Coding style

How the code looks, independent of what it does: whitespace, casing, naming, quotes, the shape of a file, the shape of a SQL statement. Everything here could be checked by a formatter or a linter; the design and architecture rules (what goes into a model, pages instead of modals, Bootstrap only) stay in the area files. Read this file for every task that writes code, whatever the area.

Many of these choices are one option among equals. They are fixed anyway, because the same shape in every file of every project is worth more than any single preference. Follow them instead of reopening the discussion.

**What the style is not:** it is not PSR-12 and not what PHP-CS-Fixer, Prettier or an editor's default formatter produce. Double quotes, `action_snake_case` methods, `if(` without a space, an indented class body between `<?php` and `?>`: an agent that formats by reflex breaks all of them. Do not run a formatter over a stack.

## Files

Read this file for every task that writes code. Then open the file for each language the task touches:

| File | Open it before writing |
| --- | --- |
| [php.md](php.md) | a controller, model, `App\` class or PHP migration. Not a view, that is html.md |
| [sql.md](sql.md) | a query in a model, a migration, a seed; also how tables and columns are named |
| [html.md](html.md) | a view or layout, including its HTML |
| [css.md](css.md) | `main.css` or a component's `<style>` |
| [javascript.md](javascript.md) | inline `<script>` in a view, a file in `webroot/assets/js`, a Cypress spec |

The area files under `reference/` link to the language file they need; they do not repeat these rules.

## Whitespace

- **Four spaces** of indentation, in every language: PHP, SQL strings, HTML, CSS, JavaScript, JSON, Cypress specs. Inside the `<?php if` / `foreach` blocks of a view and inside PHP brackets too.
- **No tabs. No trailing spaces.** Files end with one newline.
- Nested structures indent one level per nesting, so the structure is visible from the left edge alone.
- **No line grows long.** There is no fixed column count; a line fits in half the screen at a normal zoom. A long list wraps one item per line, and a long expression is split into named variables rather than wrapped (`php.md`, "Long lines").

## Casing

| What | Casing | Example |
| --- | --- | --- |
| Controller, model class | `PascalCase` with suffix | `PositionController`, `ImportModel` |
| `App\` class, enum | `PascalCase` | `App\Objects\Plan` |
| Action method | `action_snake_case` | `action_details`, `action_new` |
| Other PHP method | `camelCase()` | `getByUser()`, `setState()` |
| Variable, array key from PHP | `$camelCase` | `$positionId`, `$formResult` |
| Constant, enum case | `UPPER_SNAKE` | `FILE_SIZE_1MB`, `Plan::GOLD` |
| Table | singular `snake_case` | `company`, `company_user` |
| Column | `snake_case` | `name`, `company_size_color` |
| Foreign key column | `camelCase` `<table>Id` | `userId`, `companyId` |
| Boolean column (rare, see sql.md) | `is_*`, `has_*` or a plain adjective | `is_hidden`, `has_newsletter`, `active` |
| Event timestamp column | `*_at` | `accepted_at` |
| SQL column alias | `snake_case` in backticks, counts are `CNT` | ``AS `account_email` ``, ``COUNT(*) AS `CNT` `` |
| Setting key (`z_settings.ini`) | `snake_case`, prefix grouped | `mail_host`, `s3_bucket` |
| Permission name | lowercase, dotted `area.action` | `customer.dashboard`, `support` |
| Action name (`isAction`, `Z.Request.action`) | `kebab-case` | `update-contact-workspace` |
| `data-test` value | `kebab-case`, role prefixed | `btn-create-position`, `filter-name` |
| CSS class | `kebab-case` | `shadow-hover`, `btn-accept` |
| Logger event name | short `camelCase` | `csvImportFailed` |
| Cypress login profile | lowercase `snake_case` | `customer_new` |
| Migration file | `YYYY-MM-DD_snake_case.sql` | `2026-05-21_contact_workspace.sql` |

The casing carries information: in a table, the camelCase columns are the ones pointing elsewhere; in a controller, the `action_` prefix marks what is reachable by URL.

## Names

- **All identifiers are English**, whatever the UI language (see "Language" in `SKILL.md`).
- **Expressive variable names**, because they are easier to read: `$position`, not `$p`; `$observation`, not `$o`; `$rootDirectory`, not `$rd`. Loop variables are the singular of the collection (`foreach($contacts as $contact)`).
- Abbreviations everyone uses anyway stay: `$req`, `$res`, `$sql`, `$i`, `$e`. So does a table alias inside a single SQL query (`cu` for `company_user`): it lives for a few lines and keeps the query's structure readable.
- Model method names say what comes back or what happens: reads `get()`, `getByUser()`, `getContacts()`; writes are verbs `add()`, `accept()`, `setState()`, `delete()`.
- **Comments give context, not a transcript.** A one-line comment above each step of an action or a loop ("Reroute if the integration is not connected yet", "Reset the flag so removed groups disappear") is welcome: it tells the reader what is going on and why, and it is what makes a complex file maintainable years later. Self-describing names come first, so a comment never restates a line (`// assign 3 to $a`). Comments are English, sentence case, no period. Doc blocks on every method are noise; a `/** Retrieval */` style section marker between method groups of a large class is fine.
