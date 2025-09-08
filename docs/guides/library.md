# Library Application

In the [last guide](guest-list), we learned how to set up basic [controllers](../core-features/controllers-and-actions), [models](../core-features/models) and [views](../core-features/views) to display data from the database. We focused on how to structure a project and create a simple output.

In this guide, we’ll explore how to add [permissions](../core-features/permission-system) and security to your application. Using a Library example, we will ensure that only authorized users can access specific parts of the application. This will give you a strong foundation in managing user roles and permissions within your projects.


### Resources
<details>
<summary>Database</summary>
```sql
CREATE TABLE `book` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `author` VARCHAR(255) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```sql
INSERT INTO `book` (`title`, `author`, `description`) VALUES
('1984', 'George Orwell', 'A dystopian novel about a totalitarian regime.'),
('To Kill a Mockingbird', 'Harper Lee', 'A classic novel about racial injustice in the Deep South.'),
('The Great Gatsby', 'F. Scott Fitzgerald', 'A tale of ambition, love, and the American Dream.'),
('Moby Dick', 'Herman Melville', 'A gripping story about a man’s obsession with a great white whale.'),
('Pride and Prejudice', 'Jane Austen', 'A romantic novel critiquing British society of the early 19th century.'),
('The Catcher in the Rye', 'J.D. Salinger', 'A story about teenage rebellion and identity.'),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy adventure about a hobbit’s journey to recover treasure.'),
('Brave New World', 'Aldous Huxley', 'A novel exploring a futuristic world shaped by technology and control.'),
('The Road', 'Cormac McCarthy', 'A post-apocalyptic story of survival and father-son bonding.'),
('Frankenstein', 'Mary Shelley', 'A gothic novel about the consequences of playing God.');

```
</details>

<details>
    <summary>Controller</summary>
    LibraryController
    ```php
    <?php
        class LibraryController extends z_controller {

            public function action_list(Request $req, Response $res) {
                $books = $req->getModel("Library")->getBooks();

                return $res->render("library/list", [
                    "books" => $books
                ]);
            }
        }
    ?>
    ```
</details>

<details>
    <summary>Model</summary>
    LibraryModel
    ```php
    <?php
        class LibraryModel extends z_model {

            public function getBooks() {
                $sql = "SELECT *
                        FROM `book`";
                return $this->exec($sql)->resultToArray();
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
                    <th>Author</th>
                    <th>Title</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($opt["books"] as $book) { ?>
                    <tr>
                        <td><?= $book["author"] ?></td>
                        <td><?= $book["title"] ?></td>
                        <td><?= $book["description"] ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php }]; ?>
    ```
</details>

## Setting up our Application
To start working with permissions, we first need the basic structure of our application. This guide provides pre-built files in the "Resources" section, including templates for controllers, models, and views. Using these resources ensures an organized setup and allows us to focus on implementing functionality and permissions efficiently.

## How does Permissions works?
The framework includes a robust and fully implemented permission system designed to manage access control efficiently. This system is built around roles and the associated **permissions** assigned to those roles.

#### Key Features:

A user can have multiple roles, and each role can be assigned multiple permissions.  
This flexible structure allows for precise and scalable access control throughout the application.

#### Database Structure:

The permission data is stored in the database across several tables:

- **z_user**: Contains information about registered users of the application.
- **z_role**: Lists all roles available in the system.
- **z_role_permissions**: Defines which permissions are assigned to each role.
- **z_user_role**: Tracks which roles are assigned to each user.

#### Permission Format:

Permissions are structured using a dot-separated hierarchy, for example:  
`library.view`  

This format makes it easy to organize permissions by area or functionality, ensuring clarity and scalability when defining access levels within the application.

## How to use Permissions in a Controller
In our Library application, we want to restrict access to the book list so that only authorized users can view it.  

To enforce this restriction, we need to add the following code to our controller action:
```php
<?php
    class LibraryController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $req->checkPermission("library.list");

            $books = $req->getModel("Library")->getBooks();

            return $res->render("library/list", [
                "books" => $books
            ]);
        }
    }
?>
```
#### Explanation
The line `$req->checkPermission("library.list")` verifies if the currently logged-in user has the `library.list` permission.  

- **If the user has the permission**: The request continues, and the book list is display.  
- **If the user lacks the permission**: The framework automatically redirects them to a `403 Forbidden` page, ensuring they cannot access restricted areas of the application.

## How to use Permissions in a View
Checking permissions directly in a view can be very useful for controlling the visibility of specific elements based on the user's access level.  

In this example, we will check if the user has the `library.delete` permission. If they do, they will see a button to delete a book. Otherwise, a message saying "No permissions" will be displayed.

```php
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Author</th>
                <th>Title</th>
                <th>Description</th>
                <td>Manage</td>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["books"] as $book) { ?>
                <tr>
                    <td><?= $book["author"] ?></td>
                    <td><?= $book["title"] ?></td>
                    <td><?= $book["description"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.delete")) { ?>
                            <button>Delete</button>
                        <?php } else { ?>
                            <i>No Permissions</i>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php }]; ?>
```
#### Explanation

- **Opening PHP**: We start by opening PHP to write server-side logic. Here, we access the `$opt` object, which contains the current user (`$opt["user"]`) and the books to be displayed.
- **Getting the User Object**: The `$opt["user"]` object represents the currently logged-in user. Using its `checkPermission()` method, we verify if the user has the required permission (`library.edit`).
- **Checking Permissions**: The checkPermission(`"library.delete"`) method returns `true` if the user has the required permission, and `false` otherwise.
- **Closing PHP for HTML Rendering**: Inside the `if` condition, we close PHP to render the HTML (e.g., a button for editing the book). If the user lacks the permission, the `else` block displays a message (`No Permissions`).

## How to create Roles and Permissions
Now that we understand how to use permissions, it’s essential to know how to create them. Here’s a step-by-step guide using the admin panel provided by the framework:

1) **Access the Admin Panel**  
Open your browser and navigate to the admin panel at `http://localhost:8080/z`.

2) **Login In as Admin**  
Use the default administrator credentials:

- Email: `admin@zierhut-it.de`
- Password: `password`

3) **Create a New Role**  

- Once logged in, click on the `Roles` section in the admin panel.
- Click the `Create Role` button
- Enter a name for the role, e.g., `Library`.
- In the permissions section, add the following permissions:
    - `library.list`
    - `library.delete`
- Click **Submit** to save the role and its permissions.

4) **Assign Role to a User**

- In the navigation panel, select **Edit Users**
- Choose the user you want to assign the role to.
- Under the **Roles**, select your created role and click **Submit** to save the changes.

Now the suer assigned with your role will have access to the `library.delete` and `library.edit` permissions, ensuring they can access and manage the respective parts of the application securely.

### Summary

- **Roles**: Create roles like `Library` to group relevant permissions.
- **Permissions**: Assign permissions such as `library.list` to control access to specific features
- **Access Control**: Enforce permission checks in controllers and views to ensure only authorized users can access specific features.

This guide highlights how to implement a secure, permission-based access control system for your application.

## Next Guide
As you can see, we’ve implemented a button that is visible only to users with the `library.delete` permission. However, the button currently has no functionality. In the next guide, we’ll explore how to create backend requests to handle actions triggered from the view, enabling dynamic and secure interactions between the front-end and the back-end.

[Shop](shop)