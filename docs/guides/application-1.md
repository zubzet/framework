# Guest List Application

This guide will walk you through the creation of a simple guest list application step-by-step. This application allows you to display their details. You will learn the basic functions of the framework and how to use them effectively in your application.

### Resources
<details>
<summary>Database</summary>
```sql
CREATE TABLE `guest` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(255) NOT NULL,
    `last_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL.
    `active` TINYINT NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```sql
INSERT INTO `guest` (`first_name`, `last_name`, `email`) VALUES
('Michael', 'Hill', 'michael.hill@example.com'),
('Julia', 'Conner', 'julia.conner@example.com'),
('Michael', 'Price', 'michael.price@example.com'),
('Kyle', 'Reyes', 'kyle.reyes@example.com'),
('Russell', 'Moore', 'russell.moore@example.com'),
('Jessica', 'Green', 'jessica.green@example.com'),
('Emily', 'Davis', 'emily.davis@example.com'),
('Laura', 'Smith', 'laura.smith@example.com'),
('James', 'Brown', 'james.brown@example.com'),
('Emma', 'Johnson', 'emma.johnson@example.com');
```
</details>

## Setting up the Database
To define the database structure, go to the `database/migrations` folder. Create a file named `[DATE]_guest.sql`, replacing `[DATE]` with the current date in the format `YYYY-MM-DD`. While the file name is customizable, it is recommended to follow a clear and consistent naming convention, such as including the date and a descriptive name. Then, paste the `CREATE TABLE` statement from the Resources section into the file. 

To add initial data, go to the `database/seeds` folder. Create a file named `guest.sql` and paste the `INSERT INTO` statement from the Resources section.

This separates the database structure and seed data, keeping your setup organized.

## Setting up the Controller
??? info "What is a Controller and what is an Action?"
    A **Controller** is a component of the MVC (Model-View-Controller) pattern that manages the flow of data between the model and the view. It processes user requests, executes business logic, interacts with the model to retrieve or update data, and sends the appropriate response or view back to the user. This separation of concerns ensures maintainability and clarity in application development.

    An **Action** is a specific method within a controller that is responsible for executing a particular task or process in response to a user request. Actions are mapped to URL routes and determine the logic to be performed for a given endpoint, such as processing input data, applying business rules, or initiating a redirect.

    More information can be found [here](../core-features/controllers-and-actions.md)

To handle all requests, we need to create a [Controller](../core-features/controllers-and-actions.md).
For this example, create a file named `GuestsController.php` in the `z_controllers` folder. The file name and controller name are customizable but should follow a logical naming convention for clarity and maintainability.

```php
<?php
    class GuestsController extends z_controller {

    }
?>
```
Every controller follows this structure. It is important that the class name matches the file name and that it extends `z_controller`.

To handle a request, you also need to define a method for the specific [action](../core-features/controllers-and-actions.md/#default-actions):
```php
<?php
    class GuestsController extends z_controller {

        public function action_list(Request $req, Response $res) {

        }
    }
?>
```

## Setting up the Model
To interact with the database where the guests are stored, we need a [Model](../core-features/models.md).
For this example, create a file named `GuestsModel.php` in the `z_models` folder.

```php
<?php
    class GuestsModel extends z_model {

    }
?>
```
Every model is structured similarly. Again, it is crucial that the class name matches the file name and that it extends `z_model`.  
To fetch data from the database, create a method named `getGuests()`:

```php
<?php
    class GuestsModel extends z_model {
        public function getGuests(): array {
            $sql = "SELECT *
                    FROM `guests`";

            $guestList = $this->exec($sql)->resultToArray();

            return $guestList;
        }
    }
?>
```

### Explanation:
1. **SQL Query**: Define the SQL query to retrieve the data.  
2. **Execute Query**: Use the exec function to execute the query.  
3. **Process Results**: Convert the query results into an array with resultToArray.  
4. **Return Data**: Return the guest list for further use.

## Connecting the Controller and Model

In the controller, call the model method to retrieve the data. Here's how:
```php
<?php
    class GuestsController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $guests = $req->getModel('Guests')->getGuests();
        }
    }
?>
```
Here, we get the Guests model and call the `getGuests()` method to retrieve the list of guests.

Finally, we need to render a view and pass the guest list to it.

## Creating the View
To create a [view](../core-features/views.md), add a file named `guests_list.php` in the `z_views` folder:
```php
<?php return ["body" => function ($opt) { ?>

<?php }]; ?>
```
All views follow this structure. Between the `?>` and `<?php` tags, you will add your HTML content.

In the controller, render the view like this:
```php
<?php
    class GuestsController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $guests = $req->getModel('Guests')->getGuests();

            return $res->render("guests_list.php", [
                "guests" => $guests
            ]);
        }
    }
?>
```
### Explanation
- **render**: Use this method to render a view file.
- **Variables**: Pass variables (like the guest list) as the second parameter to make them available in the view.

## Displaying Guests in the View
Create an HTML table to display the guests, with columns for `First Name`, `Last Name`, and `Email`:

```php
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
    </table>
<?php }]; ?>
```
To populate the table with guest data, loop through the `guests` array:
```php
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["guests"] as $guest) { ?>

            <?php } ?>
        </tbody>
    </table>
<?php }]; ?>
```
Now, for each guest, add a row displaying their details:
```php
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["guests"] as $guest) { ?>
                <tr>
                    <td><?= $guest["first_name"] ?></td>
                    <td><?= $guest["last_name"] ?></td>
                    <td><?= $guest["email"] ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php }]; ?>
```
### Summary
- **Model**: Define the database query in the model.
- **Controller**: Fetch the data and render the view.
- **View**: Display the data in an HTML table.

This simple application demonstrates how to connect a model, controller, and view to create a guest list application.

### Application 2
In the next guide, we’ll explore how to secure your website by implementing effective permission management, ensuring proper access control and protecting sensitive data.

[Application 2](application-2.md)
