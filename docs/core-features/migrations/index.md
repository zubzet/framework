# Migrations in ZubZet

With the release of version **1.1.0**, a dedicated **Migration system** has finally been integrated into ZubZet.

This implementation introduces a comprehensive overhaul of how database changes are handled. Migrations now feature a robust set of tools and improvements designed to make database management more powerful, secure, and intuitive for developers.

---

## Overview

The migration system is responsible for managing database schema changes in a controlled and repeatable way.

By centralizing these processes, the system provides several key advantages:

* **Enhanced Flexibility:** Migrations are more feature-rich, allowing for complex schema evolutions.
* **Streamlined Workflows:** A variety of dedicated commands are available to cover every stage of the migration lifecycle.


## Available Commands

The following commands are integrated into the [ZubZet CLI](../console-commands.md) to manage your database lifecycle efficiently.

### Import

The **Import** command is used to **execute pending migrations** and synchronize the database schema.

**Syntax:** `php zubzet migrate:import`

This command applies all pending migrations of the project, the framework and modules, sorted together by date, then index, then name, ensuring each migration is executed exactly once. If none is pending, the import prints `No pending migrations found.` and exits with code 0 without locking. Every other run prints `Importing migration: <file>` per file and ends with `Migration finished at: <time> (took <n>s)`. By default, the system will attempt to run **all available migrations**, regardless of the current environment settings, unless specific configurations are applied.


#### Safety and Validation

To prevent database inconsistency, the system performs a pre-execution validation:

* **Integrity Check:** If the process detects a **skipped migration** (a migration file dated on or before the last executed one that was never imported), it lists the affected files as a warning. Because `--force` is enabled by default, the import then continues and runs them as pending migrations in timeline order. With `--force false` the import prints `Aborting import due to skipped migrations. Use --force to ignore.` and is **aborted immediately** with exit code 1.
* **Lock Check:** If the database is locked, the import prints `Migrations are currently locked. Aborting import.` and **exits with code 1**, followed by a hint to check for parallel runs, manual migrations or a lock to clear manually.
* **Atomic Validation:** This check is performed **before any changes are made**, ensuring that your database never enters a partial or inconsistent state.

#### Error Handling

If a SQL statement of a migration fails, the import prints `Error importing <file>: <message>` and **exits with code 1**. The failure is not rolled back and the lock is **not released**:

* Migrations imported before the failing one stay applied and are recorded as executed.
* The failing migration is not recorded. Statements of that file that ran before the error stay applied, as every statement is committed on its own.
* The database stays locked. Fix the cause, run [`db:unlock-migration`](#unlock-migration) and import again.

If a migration cannot be loaded or built (for example a PHP migration whose class is missing), the import prints `Migration import failed: <message>`, **releases the lock** and still **exits with code 0**. The remaining pending migrations are not run, while those imported before it stay applied. Check the output instead of the exit code in scripts.


#### Available Options

* **`environments-included`** | **`-i`** `{environment_name}`  
Determines which environment-specific migrations are processed during the import.
> **Details:** The `default` environment is always included automatically. You can include multiple environments by repeating the flag. Once given, migrations of any other environment are skipped with `Skipping migration (not in included environments): <file>`. Environment-skipped migrations are not recorded and stay pending; the environment is checked before `skip` and `manual`.  
> **Usage:** `db:migrate -i production -i testing`


* **`environments-excluded`** | **`-e`** `{environment_name}`  
Defines specific environments that should be explicitly skipped during the migration process, each with `Skipping migration (in excluded environments): <file>`.
> **Details:** Use this to suppress migrations intended only for certain stages (e.g., `dev` or `staging`) when updating a production database. It is checked after `-i`, and `default` can be excluded too.  
> **Usage:** `db:migrate -e production`


* **`dry`** | **`-d`**  
Lists the pending migrations without executing SQL, locking the database or recording them as executed (the `Table was locked.` and `Table was unlocked.` lines are still printed).
> **Details:** Highly recommended for verifying exactly which migration files will be applied before executing them on a live system. Migration files are not loaded in dry mode, so their skip, environment, and manual markers are not evaluated — every pending migration is reported as importable.  
> **Usage:** `db:migrate -d`


* **`exclude-external`**  
Filters the import to only include local project migrations.
> **Details:** When this flag is active, any migrations provided by the **ZubZet Framework** and by modules are ignored.  
> **Usage:** `db:migrate --exclude-external`


* **`force`** | **`-f`** `[true|false]`  
Bypasses the integrity check for skipped migrations.
> **Details:** Enabled by default, so skipped files only produce a warning (listing each file) and the process runs all pending migrations, including the skipped ones, in timeline order. Omitting the option or passing it without a value (`-f` / `--force`) keeps it enabled. Pass any value PHP does not read as true (e.g. `--force false`, `--force=false`, `-f 0` or `-f no`) to restore the strict behavior, which aborts the import when a skipped migration is detected.  
> **Usage:** `db:migrate --force false`


* **`enforce-external-timeline`**  
Applies the skipped-migration check to external migrations too.
> **Details:** External migrations (those of the **ZubZet Framework** and of installed modules) are normally exempt from the skipped-migration check, so a pending one older than the last executed migration still runs without being reported. With this flag they are checked like local project migrations and count as skipped. Whether a skipped migration aborts the import is still decided by `--force`.  
> **Usage:** `db:migrate --enforce-external-timeline`


#### File Convention

Every migration file **must** be located in the following directory:

```
app/Database/migrations
```

**Note:** The path is relative to the working directory, the folder is created when missing and subfolders are scanned too. Apart from the migrations of the framework and modules, files located outside of this directory are not considered by the migration system. This strict structure ensures that the framework can reliably scan, order, and track the status of your database schema changes.


#### Filename Convention

Each migration filename **must** follow a defined and consistent format to ensure the correct execution order.

**Structure**

```text
YYYY-MM-DD_{INDEX}_{NAME}.{php/sql}

```

**Component Description**

* **`YYYY-MM-DD`** | **Required** A valid date in ISO format (*year-month-day*). This acts as the primary sorting criteria. Impossible dates are not rejected but roll over (`2025-02-30` becomes `2025-03-02`, which is the date stored).
* **`INDEX`** | **Optional** A numeric index used to distinguish and order multiple files created on the same day. `0` is not recognized as an index and would become part of the `NAME`, so start at `1`.
* **`NAME`** | **Required** A descriptive name identifying the purpose of the migration (e.g., `create_users_table`). It must not be empty.

The year must be `2000` or later; earlier dates abort with a `History error`. Migrations touching `z_*` tables must be dated after the bundled one creating them (the bundled timeline starts at `2021-02-04_zubzet.sql`).


**Examples**

```text
2026-03-03_1_CreateUserTable.php
2026-03-03_AddStatusToOrders.sql

```

> **Important:** If a migration file **does not follow this filename convention**, the migration system will **throw an error** and abort the import process immediately to prevent sequence errors.

> **Identity:** A migration is identified by its `NAME` part only (everything after the date and the optional index, including the file extension). Whether a file is pending or already executed is decided by comparing this name with the `z_version` table, so the date and index are ignored. A file with the same `NAME` on another date or with another index therefore counts as **already executed** and is never run. Use a new `NAME` for every new migration. Two files with the same filename in different roots or subfolders abort `import` and `sync`.


#### Supported Migration File Types

The Migration system supports **two different file formats**, which can be mixed freely within your project. Other files are ignored.

- **SQL Migration Files (`.sql`)**

    You can use `.sql` files to define **raw SQL statements**. All statements found in these files are executed directly against the database in the order they appear. Each file is split on every `;`, so a semicolon inside a string literal, procedure or trigger body breaks the statement and `DELIMITER` is not supported. A `.sql` migration always has the environment `default` and cannot be marked skip or manual.

- **PHP Migration Files (`.php`)**

    For advanced logic, ZubZet supports **PHP-based migrations**. These files allow you to use the Schema QueryBuilder and conditional logic.

    **Class Structure:**  
    Every PHP migration must extend the base [`Migration`](../../api/classes/ZubZet-Framework-Database-Migration-Migration.html) class and implement the `execute()` method.

    ```php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2025_10_01_MigrationEnv extends Migration {

        public function execute(): void {
            $this->setEnvironment("production");

            $table = $this->tableCreate("migration_env");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->setPrimaryKey(["id"]);
            $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);
        }
    }
    ```

    **Classname Convention:**  
    The migration system automatically resolves the class name based on the filename:

    - The prefix `Migration_` is added to the filename (without extension).
    - Any hyphens (`-`) in the filename are converted to underscores (`_`).
      > Example: `2026-03-03_Test.php` becomes class `Migration_2026_03_03_Test`

    **Migration Settings**  
    The following settings can be applied **inside the `execute()` method** to control migration behavior:

    * **Skip**  
      Marks the migration as skipped. It will not be imported (`Skipping migration (marked to skip): <file>`), but it will be **marked as executed** in the database to prevent it from ever running in future updates.
      > **Usage:** `$this->skip();`


    * **Environment**  
      Assigns the migration to a specific environment (e.g., `production`, `testing`)  
      > **Usage:** `$this->setEnvironment(name);`  
      > **Default:** `default`

    * **Manual**  
		  Marks the migration as manual. When the import reaches this file, it will print `Migration requires manual execution: <file>`, stop with exit code 0 and leave the database locked.  
      > **Note** You must manually verify changes, [synchronize](#sync) the state (limit it with `--end` and `--endVersion`, otherwise later pending migrations are marked as executed too), and then re-run the import to continue.  
      > **Usage:** `$this->setManual(true);`

    **Schema Operations**  
    ZubZet utilizes **Doctrine DBAL** for its Schema QueryBuilder. You can find the full documentation [here](https://www.doctrine-project.org/projects/doctrine-dbal/en/4.4/reference/schema-representation.html#schema-representation).

    - **`tableCreate(string $name)`**  
      > Creates a new table. Returns a Table object to define columns and indexes.

        ??? example
            **SQL:**
            ```sql
            CREATE TABLE `testTable` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `name` VARCHAR(255),
                `active` TINYINT(1) DEFAULT 1,
                `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            ```

            **Doctrine:**
            ```php
            $table = $this->tableCreate("testTable");
            $table->addColumn("id", "integer", ["autoincrement" => true]);
            $table->setPrimaryKey(["id"]);
            $table->addColumn("name", "string", ["length" => 255]);
            $table->addColumn("active", "boolean", ["length" => 1, "default" => 1]);
            $table->addColumn("created", "timestamp", ["columnDefinition" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP"]);
            ```

    - **`tableDrop(string $name)`**
      > Drops an existing table

        ??? example
            **SQL:**
            ```sql
            DROP TABLE `testTable`;
            ```

            **Doctrine:**
            ```php
            $this->tableDrop("testTable");
            ```

    - **`tableRename(string $oldName, string $newName)`**
      > Renames a table

        ??? example
            **SQL:**
            ```sql
            ALTER TABLE testTable RENAME TO newName;
            ```

            **Doctrine:**
            ```php
            $this->tableRename("testTable", "newName");
            ```

    - **`run(string $sql)`**
      > Adds a raw SQL string at this position of the call order. The table methods only record actions and all SQL is generated afterwards; it then runs split at `;` like a SQL file.

        ??? example
            ```
            $this->run('
                CREATE TABLE `testTable` (
                    `id` INT PRIMARY KEY AUTO_INCREMENT,
                    `name` VARCHAR(255)
                );
            ');
            ```

    - **`tableAlter(string $name)`**
      > Modifies an existing table structure (adding/removing columns). Returns a Table object and throws for a table that did not exist before this file ran (the schema is read once per file).

        ??? example
            **SQL:**
            ```sql
            ALTER TABLE z_user ADD description LONGTEXT DEFAULT NULL;
            ```

            **Doctrine:**
            ```php
            $this->tableAlter("tableTest")
                  ->addColumn("description", "text", ["notnull" => false]);
            ```


    More examples can be found [here](./examples.md)  


### Unlock Migration

The **Unlock Migration** command is used to **manually clear the database lock** that is left behind when a migration process crashes or is interrupted.

> **Warning:** Only use this command if you are certain that no migration process is currently running. Using this while a migration is active can lead to database inconsistency.

**Syntax:** `php zubzet db:unlock-migration`

This command removes the lock state from the `z_migration_lock` table (`Migration table unlocked.`), allowing new migrations to be executed again. It creates missing migration tables first and prints `Migration table is not locked.` when nothing is locked.

#### When to Use

* A migration crashed and the database remains locked
* A manual migration was interrupted and you've resolved the issue
* You need to resume the migration process after fixing an error

### Seed

The **Seed** command is used to **completely recreate the database** and then insert **seed data**, making it ideal for **test and development environments** where a clean, reproducible database state is required.

> Running this command will **permanently delete** all existing data in your database. It is not reversible. Do **not** use this on production unless you explicitly intend to wipe the entire system.

> The database is dropped and recreated unless you pass `--skip-migrations`. This needs the `DROP` and `CREATE` database privileges, so configure `dbusername_elevated` and `dbpassword_elevated` (both must be set, otherwise `dbusername` and `dbpassword` are used) when your regular account lacks them.

#### Available Options

By default, `db:seed` executes **all** `.sql` and `.php` files found recursively in `app/Database/seed` (created when missing) and in the `app/Database/seed` folder of each installed module, whatever their names, in byte-wise path order (uppercase before lowercase, `10_` before `2_`, so zero-pad numeric prefixes). Seeds are not recorded, so running `db:seed --skip-migrations` twice inserts the data twice.

* **`skip-migrations`** | **`-s`**  
Skips the migration process and executes only the seeding step.
> **Details:** With this flag the database is **not** dropped or recreated, so existing data is kept and the seed files are executed on top of it. Use this flag when you want to populate the database with seed data without re-running migrations. This is useful when your database schema is already up-to-date and you only need to insert or update seed data without resetting the database.  
> **Usage:** `db:seed -s`

* **`environments-excluded`** | **`-e`**  
Excludes matching seed folders or files from execution.
> **Details:** Matches are path-based (relative to `app/Database/seed`). You can exclude a complete folder (for example `Environments`) or a single file including its extension (for example `Environments/Testing/Chat.sql`). Wildcards are not supported.  
> **Usage:** `db:seed -e Environments`

* **`environments-included`** | **`-i`**  
Re-includes matching seed folders or files **after** exclusions are applied; `-i` alone does not narrow the run, because every file starts selected, and an empty selector matches nothing.
> **Details:** This allows a two-step flow: first exclude a broad path, then include a specific subset again (folder or single file).  
> **Usage:** `db:seed -e Environments -i Environments/Testing`


**Execution Workflow**
When executed, the command performs the following sequence:

1. **Drop:** Delete the existing database.
2. **Create:** Recreated the database from scratch.
3. **Migrate:** Rebuilds the schema (see [Import](#import)) by running `db:migrate` in-process with default options; its result is ignored, and since this disables the console's auto exit, the process then always exits with `0`, even when a seed file failed
4. **Populate:** Executes all seed files found in `app/Database/seed`; a failing file prints `Error executing seed file <path>: <message>` and stops the run

With `--skip-migrations`, steps 1 to 3 are skipped and only the seed files run.


#### Supported Seed File Types

The Seed system supports **two different file formats**, which can be mixed freely within your project.

- **SQL Seed Files (`.sql`)**

    You can use .sql files to define **raw SQL statements**. The content of each file is buffered behind a `-- SEED FILE: <path>` marker and run through `executeMultiQuery()` (no `;` splitting) before the next PHP seed file and at the end, so an SQL error is reported against that PHP file, and one in the final run is not caught.

- **PHP Seed Files (`.php`)**

    For complex data generation or dynamic logic, ZubZet supports **PHP-based seed files**. These files must extend the base [`Seed`](../../api/classes/ZubZet-Framework-Database-Migration-Seed.html) class.

    **Class Structure:**  
    Each seed class must implement a `run()` method where all database operations are defined.
    ```php
    use ZubZet\Framework\Database\Migration\Seed;

    class TestSeed extends Seed {

        public function run() {
            $this->insert("model_test_insert", [
                "value" => "test value"
            ]);
        }
    }
    ```

    **Classname Convention:**

    - The class name must equal the **file name** (without `.php`) exactly, including its case. The file is loaded with `require_once` and the class is instantiated by that name, so it must live in the global namespace (no `namespace` declaration); a duplicate class name across files is a fatal redeclare.
    - Hyphens (`-`) in the file name are **not** converted. Since they are not valid in class names, use file names that are valid PHP class names, like `TestSeed.php`.
      > **Example:** `TestSeed.php` ➔ `class TestSeed extends Seed`


    **QueryBuilder Usage:**  
    PHP seed files utilize **[CakePHP\Database](https://book.cakephp.org/4.x/orm/query-builder.html)** for building queries through the `CanBuildQuery` trait (`dbSelect`, `dbInsert`, `dbUpdate`, `dbDelete`, `getQueryBuilder`).

    For more detailed information about the Query Builder, see: [QueryBuilder](../query-builder/index.md)

    **Registering Queries**  
    To ensure reliable execution, every query **must be explicitly registered** within the system.

    - **Automatic Registration:**  
      `$this->insert($table, array $data)` builds a single `dbInsert()` query from one row of `column => value` pairs and registers it with `addQuery()`. It returns nothing and has no `$types` parameter and no multi-row form. No further action is needed.
      ```php
      $this->insert("model_test_insert", ["value" => "test value"]);
      ```
      > The query is only queued. It runs after `run()` has finished, in the order it was added, via `db()->execQuery()` as a prepared statement with bound values.

    - **Manual Registration:**  
      For custom queries built via the QueryBuilder, you **must** use:
      ```php
      $this->addQuery($query);
      ```
      > Queries that are not added via `addQuery()` **will not be executed**. This includes queries created with `$this->dbInsert(...)`, `$this->dbUpdate(...)` or `$this->dbDelete(...)`, which only build the query and do not register it. For several rows, call `insert()` once per row.

    If you need direct access to the underlying connection (`\Cake\Database\Connection`), use:
    ```php
    $connection = $this->getQueryBuilder();
    ```

    `addQuery()` accepts a `Cake\Database\Query` and appends it to the public `$queries` array.


### Sync

The **Sync** command marks migrations as **executed** in the migration history without actually applying any changes to the database schema.

* **Manual Adjustments:** You already manually updated the database and need to tell the system "this is done."

* **Aligning History:** You are setting up an existing database with a new codebase and want to skip old migrations that are already present in the schema.

* **Conflict Resolution:** Fixing a broken migration state after manual intervention.

`skip` and `manual` markers are ignored, there is no skipped-migration detection, and outside `--dry` the command always releases the migration lock afterwards, without checking it first, so it also clears a stuck lock. Files outside the start/end range print `Skipping migration (before start date|before start version|after end date|after end version): <file>`; with nothing pending it prints `No pending migrations to synchronized found.` and still exits 0. A failure while synchronising prints `Synchronization failed: <message>` and exits 1.


#### Available Options

* **`start`** `{start_date}`  
Specifies the start date from which migrations should be synced.
> **Usage:** `db:sync --start 2026-03-01`  
> **Format:** `YYYY-MM-DD`, compared strictly, so `2005-1-1` is rejected with exit code 1.


* **`startVersion`** `{index}`  
Specifies the numeric index from which the sync should start.  
> **Note:** Requires the `--start` option to bet set (otherwise exit code 1); it is only compared for files dated exactly on the start date.
> **Usage:** `db:sync --start 2025-12-01 --startVersion 2`


* **`end`** `{end_date}`  
Specifies the end date up to which migrations should be synced.
> **Usage:** `db:sync --end 2026-03-01`  
> **Format:** `YYYY-MM-DD`, compared strictly like `--start`.


* **`endVersion`** `{index}`  
Specifies the numeric index up to which the sync should run.  
> **Note:** Requires the `--end` option to bet set (otherwise exit code 1); it is only compared for files dated exactly on the end date.  
> **Usage:** `db:sync --end 2025-12-01 --endVersion 2`


* **`environments-included`** | **`i`** `{environment_name}`  
Determines which environment-specific migrations are processed.
> **Details:** The `default` environment is always included automatically.
> **Usage:** `db:sync -i production -i testing`


* **`environments-excluded`** | **`e`** `{environment_name}`  
Defines which environments should be explicitly ignored during the sync.
> **Usage:** `db:sync -e staging`


* **`dry`** | **`d`**  
Simulates the sync process. It shows which files would be marked as executed without updating the database history.
> **Details:** Date and version filters still apply, but migration files are not loaded, so environment markers are not evaluated.  
> **Usage:** `db:sync -d`


* **`include-external`**  
Includes migrations provided by the ZubZet Framework in the sync process.
> **Usage:** `db:sync --include-external`



### Status

The **Status** command provides a quick health check of the **database lock state**.

To prevent race conditions, broken schemas, and inconsistent data, ZubZet applies a **strict database lock** during every migration process. This ensures that no two migration instances can run in parallel.

#### Lock States
The command prints `Migration Lock Status: LOCKED` or `Migration Lock Status: UNLOCKED` and reports one of the following states:

* **`LOCKED` (exit code 1)** – A migration is currently in progress, or a previous migration was interrupted (e.g., a **Manual Migration**), leaving the database protected against concurrent execution.
* **`UNLOCKED` (exit code 0)** – The database is clear (also when `z_migration_lock` does not exist yet; the command creates no tables). No migration is currently active, and new migrations can be started safely.

The exit code makes the command usable as a check in scripts, e.g. `db:status && db:migrate`.

!!! warning
    If the status remains **LOCKED** even though no migration is running, it usually means a **Manual Migration** was reached or a process **crashed**.

    To resolve this, you must:

    1. Fix the underlying issue (manually adjust the database schema if needed).
    2. Clear the migration lock using the unlock command:
    ```bash
    db:unlock-migration
    ```
    3. Resume the migration process:
    ```bash
    db:migrate
    ```


**Usage**
```bash
db:status
```


## Production Deployment

When deploying to a **Production environment**, ensuring that the migration history is perfectly aligned with the actual database schema is critical before applying new changes.

The standard workflow for a safe production update follows these two essential steps:

### 1. Synchronize (`db:sync`)

First, align the internal migration history. This ensures that any schema states already present in the database are correctly marked as "executed," preventing the system from attempting to re-run legacy migrations.

> **Usage:** `db:sync`

### 2. Migrate (`db:migrate`)

Once the history is synchronized, execute the pending migrations to bring the production database up to the latest version.

> **Usage:** `db:migrate`

!!! warning
    To avoid unexpected downtime, always perform a **Dry-Run** first to preview exactly which files will be affected.


## Internal Tables

To support the migration system, ZubZet automatically creates **two internal database tables**.
These tables are required for locking and version tracking and are managed fully by the framework.

### `z_migration_lock`

This table is used to track whether the database is currently **locked** for migrations.

It stores:

* Whether a migration lock is active (`is_locked`)
* The timestamp of when the lock was created (`locked_at`, informational)

This lock ensures that **only one migration process can run at a time**, preventing parallel execution and inconsistent states. The check and the insert are separate statements and a lock never expires, so only `db:unlock-migration` clears a stale one.


### `z_version`

This table is used to track **executed migrations**.

It stores:

* The basename (`migration_name`), date (`migration_date`), index (`migration_version`, `0` when the filename has none), sha1 file hash (`file_hash`) and `created` time of each executed migration (the hash is never compared, so edits to an executed migration go unnoticed; the `active` column is never read)

By keeping a record of executed migrations, ZubZet ensures that **migrations are never executed more than once**.

Both tables are **created automatically** when the migration system is used and **require no manual setup**.
They are an essential part of ensuring safe, repeatable, and reliable database migrations in ZubZet.