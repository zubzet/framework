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

The following commands are integrated into the ZubZet CLI to manage your database lifecycle efficiently.

### Import

The **Import** command is used to **execute pending migrations** and synchronize the database schema.

**Syntax:** `php zubzet migrate:import`

This command applies all pending migrations in their correct chronological order, ensuring each migration is executed exactly once. By default, the system will attempt to run **all available migrations**, regardless of the current environment settings, unless specific configurations are applied.


#### Safety and Validation

To prevent database inconsistency, the system performs a pre-execution validation:

* **Integrity Check:** If the process detects a **skipped migration** and no explicit configuration is provided to handle it, the import is **aborted immediately**.
* **Atomic Validation:** This check is performed **before any changes are made**, ensuring that your database never enters a partial or inconsistent state.


#### Available Options

* **`environments-included`** | **`-i`** `{environment_name}`  
Determines which environment-specific migrations are processed during the import.
> **Details:** The `default` environment is always included automatically. You can include multiple environments by repeating the flag.  
> **Usage:** `db:migrate -i production -i testing`


* **`environments-excluded`** | **`-e`** `{environment_name}`  
Defines specific environments that should be explicitly skipped during the migration process.
> **Details:** Use this to suppress migrations intended only for certain stages (e.g., `dev` or `staging`) when updating a production database.  
> **Usage:** `db:migrate -e production`


* **`dry`** | **`-d`**  
Simulates the migration process without committing any actual changes to the database.
> **Details:** Highly recommended for verifying exactly which migration files will be applied before executing them on a live system.  
> **Usage:** `db:migrate -d`


* **`exclude-external`**  
Filters the import to only include local project migrations.
> **Details:** When this flag is active, any migrations provided by the **ZubZet Framework** itself are ignored.  
> **Usage:** `db:migrate --exclude-external`


* **`force`** | **`-f`**  
Bypasses the integrity check for skipped migrations.
> **Details:** Normally, the process aborts if an older migration is detected as missing. This flag overrides that safety mechanism to run all pending migrations.  
> **Usage:** `db:migrate -f`


* **`enforce-external-timeline`**  
A targeted bypass for framework-level integrity checks.
> **Details:** Allows the migration to proceed even if migrations within the **ZubZet Framework** were skipped, while maintaining strict validation for local project migrations.  
> **Usage:** `db:migrate --enforce-external-timeline`


#### File Convention

Every migration file **must** be located in the following directory:

```
app/Database/migrations
```

**Note:** Migration files located outside of this directory are not considered by the migration system. This strict structure ensures that the framework can reliably scan, order, and track the status of your database schema changes.


#### Filename Convention

Each migration filename **must** follow a defined and consistent format to ensure the correct execution order.

**Structure**

```text
YYYY-MM-DD_{INDEX}_{NAME}.{php/sql}

```

**Component Description**

* **`YYYY-MM-DD`** | **Required** A valid date in ISO format (*year-month-day*). This acts as the primary sorting criteria.
* **`INDEX`** | **Optional** A numeric index used to distinguish and order multiple files created on the same day.
* **`NAME`** | **Required** A descriptive name identifying the purpose of the migration (e.g., `create_users_table`).


**Examples**

```text
2026-03-03_1_CreateUserTable.php
2026-03-03_AddStatusToOrders.sql

```

> **Important:** If a migration file **does not follow this filename convention**, the migration system will **throw an error** and abort the import process immediately to prevent sequence errors.


#### Supported Migration File Types

The Migration system supports **two different file formats**, which can be mixed freely within your project.

- **SQL Migration Files (`.sql`)**

    You can use `.sql` files to define **raw SQL statements**. All statements found in these files are executed directly against the database in the order they appear.

- **PHP Migration Files (`.php`)**

    For advanced logic, ZubZet supports **PHP-based migrations**. These files allow you to use the Schema QueryBuilder and conditional logic.

    **Class Structure:**  
    Every PHP migration must extend the base `Migration` class and implement the `execute()` method.

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
      Marks the migration as skipped. It will not be imported, but it will be **marked as executed** in the database to prevent it from ever running in future updates.
      > **Usage:** `$this->skip();`


    * **Environment**  
      Assigns the migration to a specific environment (e.g., `production`, `testing`)  
      > **Usage:** `$this->setEnvironment(name);`  
      > **Default:** `default`

    * **Manual**  
		  Marks the migration as manual. When the import reaches this file, it will stop and leave the database locked.  
      > **Note** You must manually verify changes, [synchronize](#sync) the state, and then re-run the import to continue.  
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
            $table->addColumn("created", "timestamp", ["default" => "CURRENT_TIMESTAMP"]);
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
      > Executes a raw SQL string within the PHP migration.

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
      > Modifies an existing table structure (adding/removing columns).

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


### Seed

The **Seed** command is used to **completely recreate the database** and then insert **seed data**, making it ideal for **test and development environments** where a clean, reproducible database state is required.

> Running this command will **permanently delete** all existing data in your database. It is not reversible. Do **not** use this on production unless you explicitly intend to wipe the entire system.

**Execution Workflow**
When executed, the command performs the following sequence:

1. **Drop:** Delete the existing database.
2. **Create:** Recreated the database from scratch.
3. **Migrate:** Rebuilds the schema (see [Import](#import))
4. **Populate:** Executes all seed files found in `app/Database/seed`


#### Supported Seed File Types

The Seed system supports **two different file formats**, which can be mixed freely within your project.

- **SQL Seed Files (`.sql`)**

    You can use .sql files to define **raw SQL statements**. All statements in these files are executed directly against the database in the order they appear.

- **PHP Seed Files (`.php`)**

    For complex data generation or dynamic logic, ZubZet supports **PHP-based seed files**. These files must extend the base `Seed` class.

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

    - The **file name** (without `.php`) is used as the **class name**.
    - Any hyphens (`-`) in the file name are automatically converted to underscores (`_`)
      > **Example:** `Test-Seed-File.php` ➔ `class Test_Seed_File extends Seed`


    **QueryBuilder Usage:**  
    PHP seed files utilize **CakePHP\Database** for building queries.

    For more detailed information about the Query Builder, see: [QueryBuilder](/docs/core-features/query-builder/)

    **Registering Queries**  
    To ensure reliable execution, every query **must be explicitly registered** within the system.

    - **Automatic Registration:**  
      Helper methods like `$this->dbInsert(...)` register the query internally. No further action is needed.

    - **Manual Registration:**  
      For custom queries built via the QueryBuilder, you **must** use:
      ```php
      $this->addQuery($query);
      ```
      > Queries that are not added via `addQuery()` **will not be executed**.

    If you need direct access to the underlying connection (`\Cake\Database\Connection`), use:
    ```php
    $connection = $this->getQueryBuilder();
    ```

    All queries must be instances of:
    `Cake\Database\Query`


### Sync

The **Sync** command marks migrations as **executed** in the migration history without actually applying any changes to the database schema.

* **Manual Adjustments:** You already manually updated the database and need to tell the system "this is done."

* **Aligning History:** You are setting up an existing database with a new codebase and want to skip old migrations that are already present in the schema.

* **Conflict Resolution:** Fixing a broken migration state after manual intervention.


#### Available Options

* **`start_date`** `{start_date}`  
Specifies the start date from which migrations should be synced.
> **Usage:** `db:sync --start 2026-03-01`  
> **Format:** `YYYY-MM-DD`


* **`startVersion`** `{index}`  
Specifies the numeric index from which the sync should start.  
> **Note:** Requires the `--start` option to bet set
> **Usage:** `db:sync --start 2025-12-01 --startVersion 2`


* **`end_date`** `{end_date}`  
Specifies the end date up to which migrations should be synced.
> **Usage:** `db:sync --end 2026-03-01`  
> **Format:** `YYYY-MM-DD`


* **`endVersion`** `{index}`  
Specifies the numeric index up to which the sync should run.  
> **Note:** Requires the `--end` option to bet set  
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
> **Usage:** `db:sync -d`


* **`include-external`**  
Includes migrations provided by the ZubZet Framework in the sync process.
> **Usage:** `db:sync --include-external`



### Status

The **Status** command provides a quick health check of the **database lock state**.

To prevent race conditions, broken schemas, and inconsistent data, ZubZet applies a **strict database lock** during every migration process. This ensures that no two migration instances can run in parallel.

#### Lock States
The command reports one of the following states:

* **`LOCKED` (0)** – A migration is currently in progress, or a previous migration was interrupted (e.g., a **Manual Migration**), leaving the database protected against concurrent execution.
* **`UNLOCKED` (1)** – The database is clear. No migration is currently active, and new migrations can be started safely.

!!! warning
    If the status remains **LOCKED** even though no migration is running, it usually means a **Manual Migration** was reached or a process **crashed**.

    To resolve this, you must either:

    1. Complete the manual task and [Synchronize](#sync) the state.
    2. Manually clear the lock (if you are certain no process is active).


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

* Whether a migration lock is active
* The timestamp of when the lock was created

This lock ensures that **only one migration process can run at a time**, preventing parallel execution and inconsistent states.


### `z_version`

This table is used to track **executed migrations**.

It stores:

* The name of each executed migration

By keeping a record of executed migrations, ZubZet ensures that **migrations are never executed more than once**.

Both tables are **created automatically** when the migration system is used and **require no manual setup**.
They are an essential part of ensuring safe, repeatable, and reliable database migrations in ZubZet.