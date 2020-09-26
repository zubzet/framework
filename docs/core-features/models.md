# Getting Started: Models
## What does a model do?
A model depicts all interactions with your data structure. This is usually either the database or a file. The model can be used to retrieve data as well as set it.

## How to use it
If you have already created a model, you can simply use [`$req->getModel("mMdelname")`](https://zdoc.zierhut-it.de/classes/Request.html#method_getModel)

### Built-In Functionality
Every model iherties all of [these methods](https://zdoc.zierhut-it.de/classes/z_model.html) from the z_model class. You can simply use the already existing methods and build on them. 

### Example Model
```php
<?php
    class ExampleModel extends z_model {

        public function getTest() {
        
        }

    }
?>
```

### Example Database Request
The method [`exec`](https://zdoc.zierhut-it.de/classes/z_model.html#method_exec) is probably the most important one for your model. It incorporates all the steps for a prepared statement in one simple line. The first parameter is the sql command as a variable or string literal. The second parameter is a string literal of all the variable types. Each char represents one variable. All parameters afterwards are expected to be variables and replace the question marks within the sql command.

#### Prepared types
| Type | Description | Use Cases                                    |
| ---- | ----------- | ------------------------------------------- |
| i    | An integer  | Mostly IDs, but also other numeric values   |
| s    | A string    | Names, Dates, Emails                        |
| d    | A double    | A rational number                           |
| b    | A blob      | Binary data like an image. (Don't use this) |

#### Code
```php
$sql = "YOUR COMMAND ?, ?";
$this->exec($sql, "si", $stringVar, $intVar);
return $this->resultToArray();
```