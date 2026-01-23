# Migrations in ZubZet

Since version **1.1.0**, the **Migration system** in ZubZet has been **completely refactored**.

As part of this overhaul, migrations received many new features and improvements, making them more powerful, safer, and easier to manage.


## Overview

The migration system is responsible for managing database schema changes in a controlled and repeatable way.

With the new system:

* Migrations are more flexible and feature-rich
* Multiple commands are available to cover different migration workflows


## Available Commands

ZubZet provides the following migration-related commands.

### Import

The **Import** command is used to **execute migrations**.

It applies all pending migrations to the database in the correct order and ensures that each migration is only executed once.

#### Available Options

* **environments-included** (Alias: `i`) `{environment_name}`  
  Defines which environments should be included in the sync.  
  *Default: `default`.*

* **environments-excluded** (Alias: `e`) `{environment_name}`  
  Defines which environments should be excluded from the sync.

* **dry** (Alias: `d`)  
  Runs the sync in dry-run mode without applying any changes.

* **exclude-external**  
  Excludes Framework migrations from the sync.

* **force**  
  Execute the migrations without checking for skipped migrations

* **enforce-external-timeline**
  Execute the migrations without checking for skipped migrations across ZubZet-Migrations and App-Migrations


#### Filename Convention

Each filename **must** follow a defined and consistent format.

**Structure**

```
YYYY-MM-DD_{INDEX}_{NAME}
```

**Component Description**

* **YYYY-MM-DD** – Required
  A valid date in ISO format (year-month-day)
* **INDEX** – Optional
  A numeric index used to distinguish multiple files created on the same date
* **NAME** – Required
  A descriptive name identifying the file

**Examples**

```
2025-10-10_1_Test.php
2025-10-10_Test.php
```


#### Supported Migration File Types

The Migration system supports **two different file formats**, which can also be mixed freely.

- **SQL Migration Files (`.sql`)**

    You can use `.sql` files to define **raw SQL statements**.

    All SQL statements found in these files are executed directly against the database.
    This is useful for simple inserts, static datasets, or database-specific SQL features.

- **PHP Migration Files (`.php`)**

    For more advanced migration logic, ZubZet supports **PHP-based migration files**.

    These files must follow a specific structure and extend the base `Migration` class.  

    **Structure:**
    ```php
    use ZubZet\Framework\Migration\Migration;

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

    Each migration class must implement a `run()` method.  
    All database operations are defined inside this method.

    **Classname:**

    - "Migration" plus the file name (without `.php`) is used as the class name
    - Any hyphens (`-`) in the file name are automatically converted to underscores (`_`)

    Example:
    ```php
    2025-10-01_Test.php
    ```

    Must define the following class:
    ```php
    class Migration_2025_10_01_Test extends Migration {
        public function execute(): void {
            // ...
        }
    }
    ```

    **Settings**

    * **Skip**

        Marks a migration as *skipped*, so it will not be imported when running the `migrate` command.  
        **Usage:** `$this->skip();`

    * **Environment**

        Assigns an environment to a migration to control when it should be imported.  
        **Usage:** `$this->setEnvironment(name);`  
        **Default:** `default`

    * **Manual**

        Marks a migration as *manual* and stops the import process when this migration is reached.
        Useful if you want to run specific migrations manually to verify that everything works as expected.  
        **Usage:** `$this->setManual(true);`


    **Usage**

    We are Using `Doctrine` to Support a Schema QueryBuilder.  
    Doctrine Documentation: [Doctrine](https://www.doctrine-project.org/projects/doctrine-dbal/en/4.4/reference/schema-representation.html#schema-representation).  
    The PHP Migrations support the following Schema Update Statements:

    - **tableCreate(`name`)**

        This is used to create Tables.

        **Example:**

        SQL:
        ```
        CREATE TABLE `testTable` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `name` VARCHAR(255),
            `active` TINYINT(1) DEFAULT 1,
            `created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        ```

        Doctrine: 
        ```php
        $table = $this->tableCreate("testTable");
        $table->addColumn("id", "integer", ["autoincrement" => true]);
        $table->setPrimaryKey(["id"]);
        $table->addColumn("name", "string", ["length" => 255]);
        $table->addColumn("active", "boolean", ["length" => 1, "default" => 1]);
        $table->addColumn("created", "timestamp", ["length" => 1, "default" => "CURRENT_TIMESTAMP"]);
        ```

    - **tableDrop(`name`)**
        This is used to create Tables.

        **Example:**
        SQL:
        ```
        DROP TABLE `testTable`;
        ```

        Doctrine: 
        ```php
        $this->tableDrop("testTable");
        ```

    - **tableRename(`name`)**
        This is used to rename Tables.

        **Example:**
        SQL:
        ```
        ALTER TABLE testTable RENAME TO newName;
        ```

        Doctrine: 
        ```php
        $this->tableRename("testTable", "newName");
        ```

    - **run(`sql`)**
        This is used to execute custom SQL

        **Example:**
        ```sql
        $this->run('
            CREATE TABLE `testTable` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `name` VARCHAR(255)
            );
        ');
        ```

    - **tableAlter(`name`)**
        This is used to edit Tables

        **Example:**
        SQL:
        ```
        ALTER TABLE z_user ADD description LONGTEXT DEFAULT NULL
        ```

        Doctrine: 
        ```php
        $this->tableAlter("tableTest")
                ->addColumn("description", "text", ["notnull" => false]);
        ```


### Seed

The **Seed** command is used to **completely recreate the database** and then insert **seed data**, making it ideal for **test and development environments** where a clean, reproducible database state is required.


When executed, the command performs the following steps:

1. Drops the existing database
2. Recreates the database
3. Rebuilds the schema
4. Executes all seed files found in `./app/Database/seed`


#### Supported Seed File Types

The Seed system supports **two different file formats**, which can also be mixed freely.

- **SQL Seed Files (`.sql`)**

    You can use `.sql` files to define **raw SQL statements**.

    All SQL statements found in these files are executed directly against the database.
    This is useful for simple inserts, static datasets, or database-specific SQL features.

- **PHP Seed Files (`.php`)**

    For more advanced seeding logic, ZubZet supports **PHP-based seed files**.

    These files must follow a specific structure and extend the base `Seed` class.  

    **Structure:**
    ```php
    use ZubZet\Framework\Migration\Seed;

    class TestSeed extends Seed {

        public function run() {
            $this->insert("model_test_insert", [
                "value" => "test value"
            ]);
        }
    }
    ```

    Each seed class must implement a `run()` method.  
    All database operations are defined inside this method.

    **Classname:**

    - The file name (without `.php`) is used as the class name
    - Any hyphens (`-`) in the file name are automatically converted to underscores (`_`)

    Example:
    ```powershell
    Test-Seed-File.php
    ```

    Must define the following class:
    ```php
    class Test_Seed_File extends Seed {
        public function run() {
            // ...
        }
    }
    ```

    **QueryBuilder:**  
    PHP seed files use **CakePHP\Database** as their Query Builder.

    For more detailed information about the Query Builder, see: [QueryBuilder](/docs/core-features/query-builder/)

    - Every query **must be explicitly added** using:
        ```php
        $this->addQuery($query);
        ```

        **Except** for queries that are executed via helper methods such as

        ```php
        $this->insert(...)
        ```

        These helper methods automatically register the underlying query internally, so no manual call to `addQuery()` is required.

        Queries that are not added via `addQuery(Query)` **will not be executed**.

    - A `Query` is always an instance of:
        ```php
        Cake\Database\Query
        ```

    - You can access the underlying Query Builder connection (`\Cake\Database\Connection`) via:
        ```php
        $this->getQueryBuilder();
        ```

    This ensures that all seed queries are collected, processed, and executed reliably by ZubZet.


### Sync

The **Sync** command marks migrations as **executed** without actually running them.

This is useful when:

* the database schema already matches the defined migrations
* you need to align migration history without making changes to the database


#### Available Options

* **start `{start_date}`**  
  Specifies the start date from which migrations should be synced  
  *(Format: `YYYY-MM-DD`)*

* **startVersion `{start_version}`**  
  Specifies the version from which the sync should start.  
  *Requires the `start` option.*

* **end `{end_date}`**  
  Specifies the end date up to which migrations should be synced  
  *(Format: `YYYY-MM-DD`)*

* **endVersion `{end_version}`**  
  Specifies the version up to which the sync should run.  
  *Requires the `end` option.*

* **environments-included** (Alias: `i`) `{environment_name}`  
  Defines which environments should be included in the sync.  
  *Default: `default`.*

* **environments-excluded** (Alias: `e`) `{environment_name}`  
  Defines which environments should be excluded from the sync.

* **dry** (Alias: `d`)  
  Runs the sync in dry-run mode without applying any changes.

* **exclude-external**  
  Excludes Framework migrations from the sync.


### Status

The **Status** command shows the **current migration lock state** of the database.

While a migration is running, ZubZet applies a **database lock** to ensure that **no two migrations run in parallel**. This prevents race conditions, broken schemas, and inconsistent migration states.

The command checks whether this lock is currently active and reports one of the following states:

* **LOCKED (0)** – A migration is currently running or the database is protected against concurrent migration execution.
* **UNLOCKED (1)** – No migration is running and migrations can be executed safely.


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



## Summary

* Since **1.1.0**, the migration system was **rebuilt**
* Migrations now provide many new features
* ZubZet offers multiple commands to manage different migration workflows:

This new migration system ensures reliable, consistent, and safe database evolution within ZubZet.
