# Getting Started: Models
## What does a model do?
A model depicts all interactions with your data structure. This is usually either the database or a file. The model can be used to retrieve data as well as set it.

## How to use it
If you have already created a model, you can simply use `$req->getModel("Modelname")` in a controller action to call the model.  
Otherwise, if you are within a model and need to call another model, you can use `$this->getModel("Modelname")`.

### Built-In Functionality
Every model inherits many useful methods from the z_model class. You can simply use the already existing methods and build on them.

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


The method `exec` is probably the most important one for your model. It incorporates all the steps for a prepared statement in one simple line. The first parameter is the sql command as a variable or string literal. The second parameter is a string literal of all the variable types. Each char represents one variable. All parameters afterwards are expected to be variables and replace the question marks within the sql command.

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
| Type             | Description |
| ---------------- | ----------- |
| resultToLine()   | Converts the first row of the query result into a flat array (one-dimensional), often useful when fetching a single row of data. |
| resultToArray()  | Converts the result set of a query into an array where each row is represented as an associative array.|
| getInsertId()    | Get the last insert id. |
| countResults     | Returns the number of results in the last query. |

### Tips
??? danger "Why not simply write queries without question marks?"
    When not preparing your variables, it is very likely that your code is vulnerable to SQl injections, one of the most common security mistakes made when dealing with databases.

    If you always use exec with the question marks in your queries, you save yourself from a lot of headaches.

    Learn more about SQL injections from the <a href="httpds://www.php.net/manual/en/security.database.sql-injection.php">official PHP documentation</a>. An interesting and even partly entertaining read.

