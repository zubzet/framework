# Doctrine Examples

## Create Tables

### Example 1: Basic Table
```php
public function execute(): void {
    $table = $this->tableCreate("TestTable");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);
}
```

This would result in the following MySQL query:

```sql
CREATE TABLE TestTable (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
```

### Example 2: Table with Timestamps and Defaults
```php
public function execute(): void {
    $table = $this->tableCreate("TestTable");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);
    $table->addColumn("active", "boolean", ["default" => true]);
    $table->addColumn("created", "datetime", [
        "default" => "CURRENT_TIMESTAMP"
    ]);
    $table->addColumn("updated", "datetime", [
        "default" => "CURRENT_TIMESTAMP",
        "columnDefinition" => "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
    ]);
}
```

This would result in the following MySQL query:

```sql
CREATE TABLE TestTable (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    active TINYINT(1) DEFAULT 1 NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
```

### Example 3: Table with Foreign Key Reference
```php
public function execute(): void {
    $table = $this->tableCreate("orders");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("userId", "integer", ["notnull" => true]);
    $table->addColumn("total", "decimal", ["precision" => 10, "scale" => 2]);
    $table->addColumn("status", "string", ["length" => 50, "default" => "pending"]);
    $table->addColumn("created", "datetime", ["default" => "CURRENT_TIMESTAMP"]);
    $table->addIndex(["userId"], "idx_orders_user");
}
```

This would result in the following MySQL query:
```sql
CREATE TABLE orders (
    id INT AUTO_INCREMENT NOT NULL,
    userId INT NOT NULL,
    total NUMERIC(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending' NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    INDEX idx_orders_user (userId),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
```

### Example 4: Table with Unique Constraint
```php
public function execute(): void {
    $table = $this->tableCreate("users");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("email", "string", ["length" => 255, "notnull" => false]);
    $table->addColumn("username", "string", ["length" => 100, "notnull" => true]);
    $table->addUniqueIndex(["email"], "uq_users_email");
    $table->addUniqueIndex(["username"], "uq_users_username");
}
```

This would result in the following MySQL query:
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    username VARCHAR(100) NOT NULL,
    UNIQUE INDEX uq_users_email (email),
    UNIQUE INDEX uq_users_username (username),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB
```

---

## Alter Tables

### Example 1: Add a Column
```php
public function execute(): void {
    $this->tableAlter("users")
        ->addColumn("description", "text", ["notnull" => false]);
}
```

This would result in the following MySQL query:

```sql
ALTER TABLE z_user ADD description LONGTEXT DEFAULT NULL
```

### Example 2: Add Multiple Columns
```php
public function execute(): void {
    $table = $this->tableAlter("users");
    $table->addColumn("phone", "string", ["length" => 20, "notnull" => false]);
    $table->addColumn("address", "text", ["notnull" => false]);
    $table->addColumn("birthdate", "date", ["notnull" => false]);
}
```

This would result in the following MySQL query:
```sql
ALTER TABLE z_user
    ADD phone VARCHAR(20) DEFAULT NULL,
    ADD address LONGTEXT DEFAULT NULL,
    ADD birthdate DATE DEFAULT NULL
```

### Example 3: Add Column with Index
```php
public function execute(): void {
    $table = $this->tableAlter("orders");
    $table->addColumn("status", "string", ["length" => 50, "default" => "pending"]);
    $table->addIndex(["status"], "idx_orders_status");
}
```

This would result in the following MySQL query: 
```
ALTER TABLE orders ADD status VARCHAR(50) DEFAULT 'pending' NOT NULL;
CREATE INDEX idx_orders_status ON z_user (status)
```

---

## Rename Tables

### Example: Rename a Table
```php
public function execute(): void {
    $this->tableRename("old_table_name", "new_table_name");
}
```

This would result in the following MySQL query:

```sql
ALTER TABLE old_table_name RENAME TO new_table_name
```

---

## Drop Tables

### Example: Drop a Table
```php
public function execute(): void {
    $this->tableDrop("obsolete_table");
}
```

This would result in the following MySQL query:

```sql
DROP TABLE obsolete_table
```

---

## Execute Raw SQL

### Example 1: Insert Data
```php
public function execute(): void {
    $table = $this->tableCreate("categories");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);

    $this->run("
        INSERT INTO categories (name) VALUES
        ('Electronics'),
        ('Clothing'),
        ('Books');
    ");
}
```

This would result in the following MySQL query:
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT NOT NULL, 
    name VARCHAR(255) NOT NULL, PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

INSERT INTO categories (name) VALUES
    ('Electronics'),
    ('Clothing'),
    ('Books');
```

### Example 2: Complex SQL Operations
```php
public function execute(): void {
    $this->run("
        ALTER TABLE users
        ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER email,
        MODIFY COLUMN email VARCHAR(255) NULL,
        ADD UNIQUE KEY uq_user_email (email);
    ");
}
```

This would result in the following MySQL query:
```sql
ALTER TABLE users
    ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER email,
    MODIFY COLUMN email VARCHAR(255) NULL,
    ADD UNIQUE KEY uq_user_email (email);
```

---

## Combined Operations

### Example: Full Migration with Multiple Operations
```php
public function execute(): void {
    // Create a new table
    $table = $this->tableCreate("products");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("name", "string", ["length" => 255, "notnull" => true]);
    $table->addColumn("price", "decimal", ["precision" => 10, "scale" => 2]);
    $table->addColumn("active", "boolean", ["default" => true]);
    $table->addColumn("created", "datetime", ["default" => "CURRENT_TIMESTAMP"]);

    // Alter an existing table
    $this->tableAlter("orders")
        ->addColumn("product_id", "integer", ["notnull" => false]);

    // Rename a table
    $this->tableRename("old_categories", "categories");

    // Drop an obsolete table
    $this->tableDrop("temp_data");

    // Insert seed data
    $this->run("
        INSERT INTO products (name, price) VALUES
        ('Product A', 19.99),
        ('Product B', 29.99);
    ");
}
```

This would result in the following MySQL query:
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price NUMERIC(10, 2) NOT NULL,
    active TINYINT(1) DEFAULT 1 NOT NULL,
    created DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

ALTER TABLE z_user ADD product_id INT DEFAULT NULL;

ALTER TABLE old_categories RENAME TO categories;

DROP TABLE temp_data;

INSERT INTO products (name, price) VALUES
    ('Product A', 19.99),
    ('Product B', 29.99);
```

---

## Migration Settings

### Example 1: Environment-Specific Migration
```php
public function execute(): void {
    $this->setEnvironment("production");

    $table = $this->tableCreate("production_logs");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
    $table->addColumn("message", "text");
    $table->addColumn("created", "datetime", ["default" => "CURRENT_TIMESTAMP"]);
}
```

### Example 2: Skip Migration
```php
public function execute(): void {
    $this->skip();

    // This migration will be marked as executed but not actually run
    $table = $this->tableCreate("skipped_table");
    $table->addColumn("id", "integer", ["autoincrement" => true]);
    $table->setPrimaryKey(["id"]);
}
```

### Example 3: Manual Migration
```php
public function execute(): void {
    $this->setManual(true);

    // Import will stop here and wait for manual verification
    $this->run("
        UPDATE users SET role = 'admin' WHERE id = 1;
    ");
}
```

---

## Column Types Reference

Doctrine DBAL supports the following common column types:

| Type | Description | Options |
|------|-------------|---------|
| `integer` | Integer values | `autoincrement`, `unsigned` |
| `smallint` | Small integer | `autoincrement`, `unsigned` |
| `bigint` | Large integer | `autoincrement`, `unsigned` |
| `string` | VARCHAR | `length` (required) |
| `text` | LONGTEXT | - |
| `boolean` | TINYINT(1) | `default` |
| `decimal` | DECIMAL | `precision`, `scale` |
| `float` | FLOAT | `precision` |
| `datetime` | DATETIME | `default` |
| `timestamp` | TIMESTAMP | `default` |
| `date` | DATE | `default` |
| `time` | TIME | `default` |
| `blob` | BLOB | - |
| `json` | JSON | - |

### Common Column Options

| Option | Description | Example |
|--------|-------------|---------|
| `notnull` | NOT NULL constraint | `["notnull" => true]` |
| `default` | Default value | `["default" => "value"]` |
| `length` | String length | `["length" => 255]` |
| `autoincrement` | Auto increment | `["autoincrement" => true]` |
| `unsigned` | Unsigned integer | `["unsigned" => true]` |
| `precision` | Decimal precision | `["precision" => 10]` |
| `scale` | Decimal scale | `["scale" => 2]` |
| `columnDefinition` | Raw SQL definition | `["columnDefinition" => "..."]` |

For more details, see the [Doctrine DBAL Documentation](https://www.doctrine-project.org/projects/doctrine-dbal/en/4.4/reference/schema-representation.html).