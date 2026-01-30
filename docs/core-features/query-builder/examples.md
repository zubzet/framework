# Query Builder Examples

This page provides detailed examples for building queries with the CakePHP Query Builder in ZubZet.

---

## Basic WHERE Conditions

### Simple Equality

```php
$query = $this->dbSelect(['id', 'name', 'email'], 'users')
              ->where(['status' => 'active']);
```

```sql
SELECT id, name, email
FROM users
WHERE status = 'active'
```

### Multiple Conditions (AND)

By default, multiple conditions in `where()` are combined with AND:

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where([
                  'status' => 'active',
                  'role' => 'admin'
              ]);
```

```sql
SELECT id, name
FROM users
WHERE status = 'active' 
AND role = 'admin'
```

### Chained WHERE (AND)

You can also chain multiple `where()` calls:

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where(['status' => 'active'])
              ->where(['role' => 'admin']);
```

```sql
SELECT id, name
FROM users
WHERE status = 'active'
AND role = 'admin'
```

---

## OR Conditions

### Using OR

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where([
                  'OR' => [
                      'role' => 'admin',
                      'id' => 1
                  ]
              ]);
```

```sql
SELECT id, name
FROM users
WHERE (
    role = 'admin' OR
    id = 1
)
```

### Combined AND and OR

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where([
                  'status' => 'active',
                  'OR' => [
                      'role' => 'admin',
                      'id' => 1
                  ]
              ]);
```

```sql
SELECT id, name
FROM users
WHERE (
    status = 'active' AND (
        role = 'admin' OR
        id = 1
        )
    )
```

---

## IN and NOT IN

### WHERE IN List

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->whereInList('id', [1, 2, 3, 4, 5]);
```

```sql
SELECT id, name
FROM users
WHERE id in (1, 2, 3, 4, 5)
```

### WHERE NOT IN List

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->whereNotInList('id', [1, 2, 3, 4, 5]);
```

```sql
SELECT id, name
FROM users
WHERE id not in (1, 2, 3, 4, 5)
```

---

## Comparison Operators (Expressions)

### Greater Than (>)

```php
$query = $this->dbSelect(['id', 'name', 'age'], 'users')
              ->where(['age >' => 18]);
```

```sql
SELECT id, name, age
FROM users
WHERE age > 18
```

### Greater Than or Equal (>=)

```php
$query = $this->dbSelect(['id', 'name', 'age'], 'users')
              ->where(['age >=' => 21]);
```

```sql
SELECT id, name, age
FROM users
WHERE age >= 21
```

### Less Than (<)

```php
$query = $this->dbSelect(['id', 'name', 'price'], 'products')
              ->where(['price <' => 100]);
```

```sql
SELECT id, name, price
FROM products
WHERE price < 100
```

### Not Equal (!=)

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where(['status !=' => 'deleted']);
```

```sql
SELECT id, name
FROM users
WHERE status != 'deleted'
```

### BETWEEN

```php
$query = $this->dbSelect(['id', 'name', 'price'], 'products')
              ->where(['price >=' => 10, 'price <=' => 100]);
```

```sql
SELECT id, name, price
FROM products WHERE (
    price >= 10 AND
    price <= 100
)
```

---

## LIKE Conditions

### Simple LIKE

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where(['name LIKE' => '%john%']);
```

```sql
SELECT id, name
FROM users
WHERE name like '%john%'
```

### NOT LIKE

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->where(['name NOT LIKE' => '%test%']);
```

```sql
SELECT id, name
FROM users
WHERE name not like '%test%'
```

---

## NULL Conditions

### IS NULL

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->whereNull('deleted_at');
```

```sql
SELECT id, name
FROM users
WHERE (deleted_at) IS NULL
```

### IS NOT NULL

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->whereNotNull('deleted_at');
```

```sql
SELECT id, name
FROM users
WHERE (deleted_at) IS NOT NULL
```

---

## Column Aliases

### Select with Aliases

```php
$query = $this->dbSelect([
                  'id',
                  'user_name' => 'name',
                  'user_email' => 'email'
              ], 'users');

$result = $this->exec($query);
```

```sql
SELECT id,
    name AS user_name,
    email AS user_email
FROM users
```

### Table Alias

```php
$query = $this->dbSelect(['u.id', 'u.name'], ['u' => 'users'])
              ->where(['u.status' => 'active']);
```

```sql
SELECT u.id, u.name
FROM users u
WHERE u.status = 'active'
```

---

## JOINs

### INNER JOIN

```php
$query = $this->dbSelect(['users.id', 'users.name', 'orders.total'], 'users')
              ->innerJoin('orders', ['users.id = orders.user_id']);
```

```sql
SELECT
    users.id,
    users.name,
    orders.total
FROM users
INNER JOIN orders orders
ON users.id = orders.user_id
```

### LEFT JOIN

```php
$query = $this->dbSelect(['users.id', 'users.name', 'orders.total'], 'users')
              ->leftJoin('orders', ['users.id = orders.user_id']);
```

```sql
SELECT
    users.id,
    users.name,
    orders.total
FROM users
LEFT JOIN orders orders
ON users.id = orders.user_id
```

### RIGHT JOIN

```php
$query = $this->dbSelect(['users.id', 'users.name', 'orders.total'], 'users')
              ->rightJoin('orders', ['users.id = orders.user_id']);
```

```sql
SELECT
    users.id,
    users.name,
    orders.total
FROM users
RIGHT JOIN orders orders
ON users.id = orders.user_id
```

### JOIN with Table Aliases

```php
$query = $this->dbSelect(['u.id', 'u.name', 'o.total'], ['u' => 'users'])
              ->leftJoin(['o' => 'orders'], ['u.id = o.user_id']);
```

```sql
SELECT u.id, u.name, o.total
FROM users u
LEFT JOIN orders o
ON u.id = o.user_id
```

### Multiple JOINs

```php
$query = $this->dbSelect([
                  'u.id', 
                  'u.name', 
                  'o.total', 
                  'p.name'
              ], ['u' => 'users'])
              ->leftJoin(['o' => 'orders'], ['u.id = o.user_id'])
              ->leftJoin(['p' => 'products'], ['o.product_id = p.id']);
```

```sql
SELECT u.id, u.name, o.total, p.name
FROM users u
LEFT JOIN orders o
ON u.id = o.user_id
LEFT JOIN products p
ON o.product_id = p.id
```

### JOIN with Additional Conditions

```php
$query = $this->dbSelect(['u.id', 'u.name', 'o.total'], ['u' => 'users'])
              ->leftJoin(['o' => 'orders'], ['u.id = o.user_id', 'o.status' => 'completed']);
```

```sql
SELECT u.id, u.name, o.total
FROM users u
LEFT JOIN orders o
ON (
    u.id = o.user_id AND
    o.status = 'completed'
)
```

---

## ORDER BY

### Simple Order

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->orderAsc("name");
```

```sql
SELECT id, name
FROM users
ORDER BY name ASC
```

### Multiple Order Columns

```php
 $query = $this->dbSelect(['id', 'name', 'created'], 'users')
                ->orderDesc("created")
                ->orderAsc("name");
```

```sql
SELECT id, name, created
FROM users
ORDER BY created DESC, name ASC
```

---

## LIMIT and OFFSET

### Limit Results

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->limit(10);
```

```sql
SELECT id, name
FROM users
LIMIT 10
```

### Limit with Offset (Pagination)

```php
$query = $this->dbSelect(['id', 'name'], 'users')
              ->limit(10)
              ->offset(20);
```

```sql
SELECT id, name
FROM users
LIMIT 10
OFFSET 20
```

---

## GROUP BY and HAVING

### Simple GROUP BY

```php
$query = $this->dbSelect(['status', 'COUNT(*) AS count'], 'users')
              ->groupBy(['status']);
```

```sql
SELECT status, COUNT(*) AS count
FROM users 
GROUP BY status
```

### GROUP BY with HAVING

```php
$query = $this->dbSelect(['status', 'COUNT(*) AS count'], 'users')
              ->groupBy(['status'])
              ->having(['COUNT(*) >' => 5]);
```

```sql
SELECT status, COUNT(*) AS count
FROM users
GROUP BY status
HAVING COUNT(*) > 5
```

---

## DISTINCT

```php
$query = $this->dbSelect(['status'], 'users')
              ->distinct();
```

```sql
SELECT DISTINCT status FROM users
```

---

## Complex Query Example

Here's a complete example combining multiple features:

```php
$query = $this->dbSelect([
                  'u.id',
                  'u.name',
                  'u.email',
                  'order_count' => 'COUNT(o.id)',
                  'total_spent' => 'SUM(o.total)'
              ], ['u' => 'users'])
              ->leftJoin(['o' => 'orders'], ['u.id = o.user_id'])
              ->where([
                  'u.status' => 'active',
                  'u.created >=' => '2025-01-01',
                  'o.status IN' => ['completed', 'shipped']
              ])
              ->group(['u.id', 'u.name', 'u.email'])
              ->having(['COUNT(o.id) >=' => 3])
              ->orderDesc('total_spent')
              ->limit(10);

$result = $this->exec($query);
```

```sql
SELECT
    u.id,
    u.name,
    u.email,
    COUNT(o.id) AS order_count,
    SUM(o.total) AS total_spent
FROM users u
LEFT JOIN orders o
ON u.id = o.user_id
WHERE (
    u.status = 'active' AND
    u.created >= '2025-01-01' AND
    o.status in ('completed','shipped')
)
GROUP BY u.id, u.name, u.email
HAVING COUNT(o.id) >= 3
ORDER BY total_spent DESC
LIMIT 10
```

---

## More Information

For the full CakePHP Query Builder documentation, visit:  
[CakePHP Database Query Builder](https://book.cakephp.org/4/en/orm/query-builder.html)
