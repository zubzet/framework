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
??? info "What is Migration and what is Seed?"
    **Migration** refers to the process of modifying a database's structure, such as adding, removing, or altering tables and columns. Migration files document these changes and allow them to be applied automatically across different environments, ensuring the database structure remains consistent and versioned.

    **Seeding** is the process of populating a database with sample or test data. It is commonly used in development and testing environments to provide realistic data for testing or to initialize the database with predefined values for consistency. Seeding is often used alongside migrations to set up the database.
To define the database structure, go to the `database/migrations` folder. Create a file named `[DATE]_guest.sql`, replacing `[DATE]` with the current date in the format `YYYY-MM-DD`. Then, paste the `CREATE TABLE` statement from the Resources section into the file. 

To add initial data, go to the `database/seeds` folder. Create a file named `guest.sql` and paste the `INSERT INTO` statement from the Resources section.

This separates the database structure and seed data, keeping your setup organized.  
While the file names are customizable, it is recommended to follow a clear and consistent naming convention, such as including the date and a descriptive name.

## Setting up the Controller
??? info "What is a Controller and what is an Action?"
    A **Controller** is a component of the MVC (Model-View-Controller) pattern that manages the flow of data between the model and the view. It processes user requests, executes business logic, interacts with the model to retrieve or update data, and sends the appropriate response or view back to the user. This separation of concerns ensures maintainability and clarity in application development.

    An **Action** is a specific method within a controller that is responsible for executing a particular task or process in response to a user request. Actions are mapped to URL routes and determine the logic to be performed for a given endpoint, such as processing input data, applying business rules, or initiating a redirect.

    More information can be found [here](../core-features/controllers-and-actions.md)

To handle all requests, we need to create a [Controller](../core-features/controllers-and-actions.md).
For this example, create a file named `GuestsController.php` in the `z_controllers` folder.  
The file name, controller name, and action name are flexible but should be chosen for clear, logical understanding and consistency.

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
??? info "What is a Model"
    A **Model** is a core component of the MVC (Model-View-Controller) pattern that represents the data structure and business logic for data processing. It is responsible for interacting with the database or other storage mediums to retrieve, store, update, or delete data. The model abstracts the data logic, allowing other components like controllers and views to work with the data seamlessly.

    The purpose of a **Model** is to separate data management and business logic from other layers, ensuring cleaner, more maintainable code. By centralizing database interactions, models promote reusability, reduce duplication, and enhance security, while allowing controllers to focus on application flow and views on presentation.

    More information can be found [here](../core-features/models.md)
To interact with the database where the guests are stored, we need a [Model](../core-features/models.md).  
For this example, create a file named `GuestsModel.php` in the `z_models` folder.  
You have flexibility in the naming of the file and model, but it is best to use a clear and logical structure for better understanding.

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
The function name is up to you, but adhering to meaningful, consistent names is important for clarity and maintainability.

### Explanation
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
If you have named your model or function differently, be sure to substitute the values with the appropriate names reflecting your implementation.

Finally, we need to render a view and pass the guest list to it.

## Creating the View
??? info "What is a View"
    A **View** is a key part of the MVC (Model-View-Controller) pattern responsible for presenting data to the user. It defines the structure and layout of the user interface, rendering dynamic content based on data provided by the controller. Views focus solely on presentation, avoiding business logic or direct data handling, to ensure a clean separation of concerns.

    More information can be found [here](../core-features/views.md)

To create a [view](../core-features/views.md), add a folder named `guests` in the `z_views` folder and inside this folder add a file named `guests_list.php`:
```php
<?php return ["body" => function ($opt) { ?>

<?php }]; ?>
```
All views follow this structure. Between the `?>` and `<?php` tags, you will add your HTML content.  
The file name should be intuitive and descriptive for easy identification.

In the controller, render the view like this:
```php
<?php
    class GuestsController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $guests = $req->getModel('Guests')->getGuests();

            return $res->render("guests/guests_list.php", [
                "guests" => $guests
            ]);
        }
    }
?>
```
A controller doesn't always need to render a view. It can handle tasks like processing requests, returning JSON for APIs, or triggering background processes.


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
The use of `$guest["first_name"]` corresponds to the column names defined in the database. These identifiers map the data retrieved from the database to the respective fields for display in the view.

### Summary
- **Model**: Define the database query in the model.
- **Controller**: Fetch the data and render the view.
- **View**: Display the data in an HTML table.

This simple application demonstrates how to connect a model, controller, and view to create a guest list application.  
To start your Website run "npm run start" inside your terminal.  
And to view your result, navigate to `http://localhost:8080/Guests/list` in your browser.

## Next Guide
In the next guide, we’ll take a closer look at how to secure your website using roles and permissions. This is a key step to make sure that only authorized users can access certain features or parts of your application. By setting up roles and assigning specific permissions, you’ll create a solid system to manage access and keep your website both secure and organized.  

[Library](library.md)