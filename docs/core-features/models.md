# Getting Started: Models
## What does a model do?
A model depicts all interactions with your data structure. This is usually either the database or a file. The model can be used to retrieve data as well as set it.

## How to use it
If you have already created a model, you can simply use `$req->getModel("Modelname")`, `$res->getModel("Modelname")` or `model("Modelname")` (the controller itself has no `getModel()`) in a [controller action](controllers-and-actions.md) to call the model.  
Otherwise, if you are within a model and need to call another model, you can use `$this->getModel("Modelname")`. An optional second argument of `getModel()` replaces the `app/Models/` directory and must end with a directory separator.

### File and class
A model lives in `app/Models/<Name>Model.php` and is a plain global-namespace class `<Name>Model` (no `namespace` declaration). `getModel("<Name>")` and `model("<Name>")` append `Model` automatically, so `getModel("Employee")` loads `app/Models/EmployeeModel.php`.

A dotted name such as `model("admin.Users")` loads `app/Models/admin/UsersModel.php` (directory parts lower-cased, class `UsersModel`); an app file shadows a bundled model of the same name (`z_adminDashboard`, `z_file`, `z_general`, `z_logger`, `z_login`, `z_migration`, `z_organization`, `z_permission`, `z_user`), and a missing model throws an `Exception` (`Model: <Name>Model does not exist!`). The loader `require_once`s the file, so class names must be unique even across sub-directories, and creates one instance per model and request with `db()` and `zubzet()`, so properties persist; never instantiate a model yourself or pass user input as the name.

### Built-In Functionality
Every model extends `z_model`, an alias of [`ZubZet\Framework\Core\Model`](../api/classes/ZubZet-Framework-Core-Model.html), and inherits its many useful methods, including the [query builder](query-builder/index.md) helpers (trait `CanBuildQuery`) and `getModel()` (trait `CanRetrieveModel`), plus the protected `$z_db` (the `Connection`) and `$booter`. You can simply use the already existing methods and build on them.

## Example Model
```php
<?php

    class EmployeeModel extends z_model {

        public function getAll(): array {
            $sql = "SELECT *
                    FROM `employee`";
            return $this->exec($sql)->resultToArray();
        }

        public function getById($employeeId): array {
            $sql = "SELECT *
                    FROM `employee`
                    WHERE `id` = ?";
            return $this->exec($sql, "i", $employeeId)->resultToLine();
        }

    }
?>
```


The method [`exec`](../api/classes/ZubZet-Framework-Core-Model.html#method_exec) is probably the most important one for your model. It incorporates all the steps for a [prepared statement](https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php) in one simple line. The first parameter is the sql command as a variable or string literal. The second parameter is a string literal of all the variable types. Each char represents one variable. All parameters afterwards are expected to be variables and replace the question marks within the sql command.

### Prepared types
| Type | Description | Use Cases                                    |
| ---- | ----------- | ------------------------------------------- |
| i    | An integer  | Mostly IDs, but also other numeric values   |
| s    | A string    | Names, Dates, Emails                        |
| d    | A double    | A rational number                           |
| b    | A blob      | Binary data like an image. (Not recommended) |

## Example Database Request

```sql
$sql = "SELECT * 
        FROM `employee` 
        WHERE `first_name` = ?
        AND `age` = ?";
$this->exec($sql, "si", "Klaus", 30);
```

### Execute Features
`exec` returns the `Connection`, so these helpers chain directly. The result and insert id are shared per request, so a query from any model replaces the previous one. `mergeAsGroup()`, `checkIfUnique()`, `checkIfExists()`, `executeMultiQuery()`, `execQuery()` and `switchUser()` exist only on `db()`, not on models. The result helpers only work after a SELECT, as INSERT, UPDATE and DELETE leave no result set.

| Type             | Description |
| ---------------- | ----------- |
| [resultToLine()](../api/classes/ZubZet-Framework-Database-Interaction.html#method_resultToLine)   | Returns the next row of the last result as an associative array, or `null` when no row is left. |
| [resultToArray()](../api/classes/ZubZet-Framework-Database-Interaction.html#method_resultToArray)  | Returns all remaining rows of the last result, each as an associative array keyed by column name with numeric columns as PHP numbers; an initial array can be passed to append to. |
| [getInsertId()](../api/classes/ZubZet-Framework-Database-Interaction.html#method_getInsertId)    | Get the last insert id; every `exec()` overwrites it (SELECTs included), but inserts done by the internal log do not. |
| `mergeAsGroup($groupBy, $subElement = null)` | `db()` only: groups the remaining rows by the `$groupBy` column (numeric-string keys become integers); each group holds the full rows, or just the `$subElement` values when set. |
| `checkIfUnique($table, $field, $value, $ignoreField = null, $ignoreValue = null)`, `checkIfExists($table, $field, $value)` | `db()` only: `true` when no row (respectively at least one row) has `$field = $value`; names are backtick-wrapped but not escaped, values are bound as `s`. The ignore pair excludes the edited row (`AND $ignoreField <> ?`), so a `null` `$ignoreValue` makes `checkIfUnique` return `true`. |
| [countResults](../api/classes/ZubZet-Framework-Database-Interaction.html#method_countResults)     | Returns the number of results in the last query. |
| `countTableEntries($table)` | Returns `COUNT(*)` of the table. |
| `getResult()` | The raw `mysqli_result\|bool\|null` of the last query. |
| `getFullTable($table, $fields = "*")` | Returns all rows of `SELECT $fields FROM $table`; both are inserted unescaped. |
| `getTableWhere($table, $fields, $where)` | Like `getFullTable` with `WHERE $where`, also inserted unescaped; optional `$types` and `$values` bind placeholders in `$where`. |

### Tips
??? danger "Why not simply write queries without question marks?"
    Only bound values are escaped, the query text is sent exactly as written. When not preparing your variables, it is very likely that your code is vulnerable to SQl injections, one of the most common security mistakes made when dealing with databases.

    If you always use exec with the question marks in your queries, you save yourself from a lot of headaches.

    Learn more about SQL injections from the <a href="https://www.php.net/manual/en/security.database.sql-injection.php">official PHP documentation</a>. An interesting and even partly entertaining read.

