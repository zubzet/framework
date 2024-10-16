# Getting Started: Models
## What does a model do?
A model depicts all interactions with your data structure. This is usually either the database or a file. The model can be used to retrieve data as well as set it.

## How to use it
If you have already created a model, you can simply use [`$req->getModel("Modelname")`](https://zdoc.zierhut-it.de/classes/Request.html#method_getModel)

### Built-In Functionality
Every model inherits all of [these methods](https://zdoc.zierhut-it.de/classes/z_model.html) from the z_model class. You can simply use the already existing methods and build on them.

## Example Model
```php
<?php

    class ExampleModel extends z_model {

        public function getTest() {
            $sql = "SELECT * FROM `test`";
            return $this->exec($sql)->resultToArray();
        }

         public function updateTest() {
            // Update stuff
        }

    }
?>
```


The method [`exec`](https://zdoc.zierhut-it.de/classes/z_model.html#method_exec) is probably the most important one for your model. It incorporates all the steps for a prepared statement in one simple line. The first parameter is the sql command as a variable or string literal. The second parameter is a string literal of all the variable types. Each char represents one variable. All parameters afterwards are expected to be variables and replace the question marks within the sql command.

###Prepared types
| Type | Description | Use Cases                                    |
| ---- | ----------- | ------------------------------------------- |
| i    | An integer  | Mostly IDs, but also other numeric values   |
| s    | A string    | Names, Dates, Emails                        |
| d    | A double    | A rational number                           |
| b    | A blob      | Binary data like an image. (Not recommended) |

## Example Database Request

```sql
    $sql = "YOUR COMMAND ?, ?";
    $this->exec($sql, "si", $stringVar, $intVar);
```

### Tips
??? danger "Why not simply write queries without question marks?"
    When not preparing your variables, it is very likely that your code is vulnerable to SQl injections, one of the most common security mistakes made when dealing with databases.

    If you always use exec with the question marks in your queries, you save yourself from a lot of headaches.

    Learn more about SQL injections from the <a href="httpds://www.php.net/manual/en/security.database.sql-injection.php">official PHP documentation</a>. An interesting and even partly entertaining read.

