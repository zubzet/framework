# Todo Application
In the [last guide](shop.md), we explored how to make backend requests and manage them effectively on the server side.

In this guide, we will learn how to create [forms](../forms/auto-form-validation.md) within our website. Using a simple Todo application as an example, we will demonstrate how to implement functionality to add data to our database using forms.

### Resources
<details>
<summary>Database</summary>
```sql
CREATE TABLE `todo` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `description` VARCHAR(255) NOT NULL,
    `completed` TINYINT NOT NULL DEFAULT 0,
    `active` TINYINT NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```sql
INSERT INTO `todo` (`description`) VALUES
('Buy groceries'),
('Clean the kitchen'),
('Finish reading the book'),
('Take the dog for a walk'),
('Pay utility bills'),
('Call mom'),
('Schedule a dentist appointment'),
('Organize workspace'),
('Update software on laptop'),
('Prepare presentation slides');
```
</details>

<details>
    <summary>Controller</summary>
    TodoController
    ```php
    <?php
        class TodoController extends z_controller {

            public function action_list(Request $req, Response $res) {
                $req->checkPermission("todo.view");

                if ($req->isAction("delete-todo")) {
                    $req->checkPermission("todo.delete");

                    $todoId = $req->getPost("todoId");

                    $req->getModel("Todo")->deleteTodo($todoId);
                    return $res->success();
                }

                if ($req->isAction("complete-todo")) {
                    $todoId = $req->getPost("todoId");

                    $req->getModel("Todo")->completeTodo($todoId);
                    return $res->success();
                }

                $todos = $req->getModel("Todo")->getTodoList();

                return $res->render("todo/list", [
                    "todos" => $todos
                ]);
            }

            public function action_add(Request $req, Response $res) {
                $req->checkPermission("todo.add");

                // Handle here the Form

                return $res->render("todo/add");
            }
        }
    ?>
    ```
</details>

<details>
    <summary>Model</summary>
    TodoModel
    ```php
    <?php
        class TodoModel extends z_model {

            public function getTodoList() {
                $sql = "SELECT *
                        FROM `todo`
                        WHERE `active` = 1";
                return $this->exec($sql)->resultToArray();
            }

            public function deleteTodo($todoId) {
                $sql = "UPDATE `todo`
                        SET `active` = 0
                        WHERE `id` = ?";
                $this->exec($sql, "i", $todoId);
            }

            public function completeTodo($todoId) {
                $sql = "UPDATE `todo`
                        SET `completed` = 1
                        WHERE `id` = ?";
                $this->exec($sql, "i", $todoId);
            }
        }
    ?>
    ```
</details>

<details>
    <summary>View</summary>
    list
    ```html
        <?php return ["body" => function ($opt) { ?>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Completed</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opt["todos"] as $todo) { ?>
                        <tr>
                            <td><?= $todo["description"] ?></td>
                            <td><?= $todo["completed"] ?></td>
                            <td>
                                <?php if($opt["user"]->checkPermission("todo.delete")) { ?>
                                    <button class="delete-todo" data-id="<?= $todo["id"] ?>">Delete</button>
                                <?php } else { ?>
                                    <i>No Permissions</i>
                                <?php } ?>

                                <button class="complete-todo" data-id="<?= $todo["id"] ?>">Complete</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>


            <?php if($opt["user"]->checkPermission("todo.delete")) { ?>
                <script>
                    $(".delete-todo").click(function() {
                        var id = $(this).data("id");

                        Z.Request.action('delete-todo', {
                            todoId: id
                        }, (res) => {
                            if(res.result == 'success') {
                                location.reload();
                                return;
                            }
                            alert("An error occurred");
                        });
                    });

                    $(".complete-todo").click(function() {
                        var id = $(this).data("id");

                        Z.Request.action('complete-todo', {
                            todoId: id
                        }, (res) => {
                            if(res.result == 'success') {
                                location.reload();
                                return;
                            }
                            alert("An error occurred");
                        });
                    });
                </script>
            <?php } ?>
        <?php }]; ?>
    ```
</details>

## Setting up our Application
To begin working with forms, we first need to establish a basic structure for our application.
You can start by copying the controllers, models, views, and database files into the section of your project. Using these resources ensures an organized setup, allowing us to focus on implementing functionality and forms effectively.

## How to create a Form?
??? info "What are Forms?"
    A form is an interactive element within a web page that allows users to input and submit data. Using the ZubZet framework, forms can be dynamically created and validated, ensuring efficient data collection and seamless integration with backend processing and database storage.

With the help of the framework, creating a form is straightforward. This form comes with several advantages, including easy management, a wide range of features, and an appealing design.

## Creating a Form in a View
To create a form in a view using ZubZet's framework, start by defining an HTML `div` element with an `id` attribute to serve as the container for the form:
```html
<?php return ["body" => function ($opt) { ?>
    <div id="form"></div>
<?php }]; ?>
```

Next, include a JavaScript block within the view. This script block will be used to configure and define the form:
```html
<?php return ["body" => function ($opt) { ?>
    <div id="form"></div>
    <script>
    </script>
<?php }]; ?>

```

To initialize the form, use the `Z.Forms.create` method from the framework's JavaScript API. The `dom` property specifies the container's ID:
```html
<?php return ["body" => function ($opt) { ?>
    <div id="form"></div>
    <script>
        var form = Z.Forms.create({
            dom: "form",
        });
    </script>
<?php }]; ?>
```

Fields can now be added to the form. For example, to include a text input field for a task description, use the createField method. Define attributes such as `name`, `type`, `text`, and `required` to specify the field's behavior:
``` html
<?php return ["body" => function ($opt) { ?>
    <div id="form"></div>
    <script>
        var form = Z.Forms.create({
            dom: "form",
        });

        form.createField({
            name: "description",
            type: "text",
            text: "Todo",
            required: true,
        });
    </script>
<?php }]; ?>
```
??? info "What does the attributes mean?"
    Each field's attributes serve specific purposes:

    - `name`: Identifies the field for backend processing.
    - `type`: Determines the input type (e.g., text, number, password, or date).
    - `text`: Displays a label for the field above the input.
    - `required`: Specifies whether the field must be filled before submission.

    There are additional attributes available to customize your fields. You can find the full list [here](../forms/auto-form-validation.md)

## Handle Form in the backend
To handle submitted form data, go to your controller which send you the view of the form. Begin by checking for the presence of form data with `$req->hasFormData()`. This function ensures the backend processes only valid form submissions:
```php
<?php
    class TodoController extends z_controller {

        // function action_list

        public function action_add(Request $req, Response $res) {
            $req->checkPermission("todo.add");

            if($req->hasFormData()) {

            }

            return $res->render("todo/add");
        }
    }
?>
```

`$req->hasFormData()` is similar to `req->isAction()`. Both methods are used to check if the frontend is requesting the backend to process a specific action.

Once it is determined that a form needs to be handled, the next step is to validate the form. In this example, we ensure that the `description` field is mandatory and that the user input is between 5 and 15 characters long.

```php
<?php
    class TodoController extends z_controller {

        // function action_list

        public function action_add(Request $req, Response $res) {
            $req->checkPermission("todo.add");

            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("description"))
                        ->required()->length(5, 15),
                ]);
            }

            return $res->render("todo/add");
        }
    }
?>
```

If validation fails, return errors to the frontend using `$res->formErrors()`:
```php
<?php
    class TodoController extends z_controller {

        // function action_list

        public function action_add(Request $req, Response $res) {
            $req->checkPermission("todo.add");

            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("description"))
                        ->required()->length(5, 15),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }
            }

            return $res->render("todo/add");
        }
    }
?>
```

`$formResult->hasErrors()` checks if there is any invalid input. If errors are present, the method return `$res->formErrors($formResult->errors);` sends the errors to the frontend, allowing them to be displayed to the user for correction.

Upon successful validation, use $res->insertDatabase to save the data to a database. Ensure field names in the frontend match database column names:
```php
<?php
    class TodoController extends z_controller {

        // function action_list

        public function action_add(Request $req, Response $res) {
            $req->checkPermission("todo.add");

            if($req->hasFormData()) {
                $formResult = $req->validateForm([
                    (new FormField("description"))
                        ->required()->length(5, 15),
                ]);

                if($formResult->hasErrors) {
                    return $res->formErrors($formResult->errors);
                }

                $res->insertDatabase("todo", $formResult);
                return $res->success();
            }

            return $res->render("todo/add");
        }
    }
?>
```
By following these steps, the form can be created, validated, and handled efficiently, ensuring both the frontend and backend work seamlessly together.

### Summary
- **Z.Forms.create**: Initializes a form within a view. It requires a `dom` attribute to specify the container where the form will be rendered.
- **createField**: Creates individual fields with various attributes to customize their behavior and appearance.
- **hasFormData()**: Similar to `req->isAction()`, it checks if the frontend is requesting the backend to handle form data.
- **validateForm**: Validates form fields based on predefined rules to ensure data meets the intended criteria.
- **formErrors**: Sends validation errors back to the frontend if any fields contain invalid input, allowing for user correction.
- **insertDatabase**: Inserts validated form data directly into the database.

## Next Guide
In the upcoming guide, we will explore how to create and use layouts to organize your website into distinct sections for better structure and maintainability.

[Layouts](layout.md)