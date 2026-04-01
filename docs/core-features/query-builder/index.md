# Query Builder in ZubZet

Since version **1.0.0**, ZubZet includes a built-in **Query Builder**.  
It is based on [CakePHP\Database](https://book.cakephp.org/4/en/orm/query-builder.html).

The Query Builder allows you to build SQL queries programmatically in a safe and structured way, directly from your models.

---

## How it works

Inside a [model](/docs/core-features/models), you can now use helper methods like `select`, `update`, `delete`, and `insert`.
These methods internally delegate to the CakePHP Query Builder.

Example methods inside a model:

```php
public function dbSelect($fields = [], $table = [], array $types = []) {
    return $this->getQueryBuilder()->selectQuery($fields, $table, $types);
}

public function dbUpdate($table = null, array $values = [], array $conditions = [], array $types = []) {
    return $this->getQueryBuilder()->updateQuery($table, $values, $conditions, $types);
}

public function dbDelete(string $table, array $conditions = [], array $types = []) {
    return $this->getQueryBuilder()->deleteQuery($table, $conditions, $types);
}

public function dbInsert(string $table = null, array $values = [], array $types = []) {
    return $this->getQueryBuilder()->insertQuery($table, $values, $types);
}

public function getQueryBuilder() {
    return db()->cakePHPDatabase;
}
```

---

## Executing Queries

Once you have built a query, you can execute it with the `exec` method:
```php

$query = $this->dbSelect(...);

$this->exec($query);
```

---

## Examples

### Select Example

```php
$query = $this->dbSelect(['id', 'name'], 'users') // Build a SELECT query on table "users", fetching only the columns "id" and "name"
              ->where(['id' => 42]); // Add a WHERE condition: only rows where "id" equals 42

$result = $this->exec($query); // Execute the query and return the result
```

This will execute a query equivalent to:

```sql
SELECT id, name FROM users WHERE id = 42;
```

---

### Insert Example

```php
$query = $this->dbInsert('users', [ // Build an INSERT query for the "users" table
    'name'  => 'John Doe', // Set column "name" to "John Doe"
    'email' => 'john@example.com' // Set column "email" to "john@example.com"
]);

$this->exec($query); // Execute the query to insert the new record
```

Equivalent SQL:

```sql
INSERT INTO users (name, email) VALUES ('John Doe', 'john@example.com');
```

---

### Update Example

```php
$query = $this->dbUpdate('users', [ // Build an UPDATE query on the "users" table
    'email' => 'new@example.com' // Set column "email" to "new@example.com"
])->where(['id' => 42]); // Add WHERE condition: only update the row where "id" equals 42

$this->exec($query); // Execute the query to apply the update
```

Equivalent SQL:

```sql
UPDATE users SET email = 'new@example.com' WHERE id = 42;
```

---

### Delete Example

```php
$query = $this->dbDelete("users") // Build a DELETE query on the "users" table
            ->where(["id" => 42]); // Add WHERE condition: only delete the row where "id" equals 42

$this->exec($query); // Execute the query to remove the record
```

Equivalent SQL:

```sql
DELETE FROM users WHERE id = 42;
```

---

## Summary

* All query methods (`select`, `insert`, `update`, `delete`) return a **CakePHP Query object**.
* Queries are executed via `$this->exec($query)`.

For more examples, see the [Examples](./examples.md) page.

---

## More Information

The Query Builder in ZubZet is built on top of **[CakePHP\Database](https://book.cakephp.org/4/en/orm/query-builder.html)**.
You can use all features provided by CakePHP’s query builder.

Important:

* **There is no direct database connection handled by CakePHP itself inside ZubZet.**
* This means that simply creating a query with `$this->getQueryBuilder()` will **not** execute it.
* You must explicitly run the query via:

    ```php
    $this->exec($query);
    ```

* The CakePHP Query Builder instance can always be accessed with:

    ```php
    $this->getQueryBuilder();
    ```

This ensures that all queries you build are properly executed through ZubZet’s database layer.