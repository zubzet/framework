# Shop Application
In the [last guide](library.md), we explored how to enhance your website's security by implementing roles and permissions to restrict user access effectively.

In this guide, we’ll focus on handling backend requests in your application. Using a Shop example, we will demonstrate how to implement functionality for removing data from the database, triggered by a delete button on the page. This will provide you with the foundation for managing interactive actions securely and efficiently.

### Resources
<details>
<summary>Database</summary>
```sql
CREATE TABLE `product` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `price` FLOAT NOT NULL,
    `active` TINYINT NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

```sql
INSERT INTO `product`(`name`, `price`) VALUES
('Laptop', 999.99),
('Smartphone', 599.49),
('Tablet', 329.89),
('Smartwatch', 199.99),
('Bluetooth Speaker', 89.99),
('Wireless Headphones', 149.99),
('Gaming Mouse', 49.99),
('Mechanical Keyboard', 119.99),
('External Hard Drive', 79.99),
('Webcam', 59.99);
```
</details>

<details>
    <summary>Controller</summary>
    ShopController
    ```php
    <?php
        class ShopController extends z_controller {

            public function action_list(Request $req, Response $res) {
                $req->checkPermission("shop.view");

                if ($req->isAction("delete-product")) {
                    $req->checkPermission("shop.delete");
                    $productId = $req->getPost("productId");

                    $req->getModel("Shop")->deleteProduct($productId);
                    return $res->success();
                }

                $products = $req->getModel("Shop")->getProducts();

                return $res->render("shop/shopping_cart", [
                    "products" => $products
                ]);
            }
        }
    ?>
    ```
</details>

<details>
    <summary>Model</summary>
    ShopModel
    ```php
    <?php
        class ShopModel extends z_model {

            public function getProducts() {
                $sql = "SELECT *
                        FROM `product`
                        WHERE `active` = 1";
                return $this->exec($sql)->resultToArray();
            }

            public function deleteProduct($productId) {
                $sql = "UPDATE `product`
                        SET `active` = 0
                        WHERE `id` = ?";
                $this->exec($sql, "i", $productId);
            }
        }
    ?>
    ```
</details>

<details>
    <summary>View</summary>
    shopping_cart
    ```html
    <?php return ["body" => function ($opt) { ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($opt["products"] as $product) { ?>
                    <tr>
                        <td><?= $product["name"] ?></td>
                        <td><?= $product["price"] ?></td>
                        <td>
                            <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                                <button>">Delete</button>
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
</details>

## Setting up our Application
To start working with permissions, we first need the basic structure of our application. This guide provides pre-built files in the "Resources" section, including templates for controllers, models, and views. Using these resources ensures an organized setup and allows us to focus on implementing functionality and backend requests efficiently.

## How Do Backend Requests work?
The framework includes a robust and fully implemented request system. Backend requests enable communication between the view and the controller. When an action is triggered in the view, such as clicking a delete button, a request is sent to the controller. The controller processes this request, performs the necessary operations—such as deleting an item from the database—and sends a response back to the view. This ensures that user actions on the front end dynamically and securely interact with the back-end logic and data.

## How to send a Backend Request?
To send a backend request, we need to update our view to handle user actions. Here, we’ll make the delete button functional by adding an event listener to manage button clicks.

```html
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["products"] as $product) { ?>
                <tr>
                    <td><?= $product["name"] ?></td>
                    <td><?= $product["price"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                            <button class="delete-product">Delete</button>
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

To send a backend request, we need to update our view to handle user actions. Here, we’ll make the delete button functional by adding an event listener to manage button clicks.

??? info "What are classes?"
    **Classes** are identifiers used in HTML elements to apply styles with CSS or add behavior with JavaScript. They can be shared across multiple elements for consistency.

    Additionally, there are **IDs**, which are unique identifiers meant to target a single element on a page.

First, assign a class to the delete button so it can be uniquely identified:
```html
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["products"] as $product) { ?>
                <tr>
                    <td><?= $product["name"] ?></td>
                    <td><?= $product["price"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                            <button class="delete-product">Delete</button>
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
The `class="delete-product"` ensures that only these buttons are targeted for the delete functionality.


??? info "What is jQuery?"
    **jQuery** is a fast, small, and feature-rich JavaScript library designed to simplify HTML document traversal, event handling, animations, and AJAX interactions. It provides an easy-to-use API that works across a multitude of browsers, making it a popular choice for enhancing web development with minimal code.

    More information can be found on the [official jQuery website](https://jquery.com).


Next, use `jQuery` to add an event listener for the buttons with the `delete-product` class. To enhance security, we ensure the script is only displayed to users who have the appropriate permissions. This prevents unauthorized users from accessing or triggering backend functionality.


```html
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["products"] as $product) { ?>
                <tr>
                    <td><?= $product["name"] ?></td>
                    <td><?= $product["price"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                            <button class="delete-product">">Delete</button>
                        <?php } else { ?>
                            <i>No Permissions</i>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php if($opt["user"]->checkPermission("library.edit")) { ?>
        <script>
            $(".delete-product").click(function() {

            });
        </script>
    <?php } ?>
<?php }]; ?>
```

??? info "What do EventListeners do?"
    **EventListeners** are JavaScript functions that wait for a specific event, such as a click or a keypress, to occur on an element. When the event happens, the listener executes a predefined action or function, enabling dynamic interactions on a webpage.

This script attaches a `click()` event listener to all elements with the delete-product class, setting up the functionality needed to handle the delete action dynamically.

Now we need to get our product-identifier ('id').
For that we need to save the id`s of the products inside the product-html-element.
We are doing it like that:
```html
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["products"] as $product) { ?>
                <tr>
                    <td><?= $product["name"] ?></td>
                    <td><?= $product["price"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                            <button class="delete-product" data-id="<?= $opt['id'] ?>">">Delete</button>
                        <?php } else { ?>
                            <i>No Permissions</i>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php if($opt["user"]->checkPermission("library.edit")) { ?>
        <script>
            $(".delete-product").click(function() {
                var id = $(this).data("id");
            });
        </script>
    <?php } ?>
<?php }]; ?>
```

With the `data-id` attribute, we can embed custom values into our HTML elements. For example, using `var id = $(this).data("id");`, we can retrieve the `id` of the selected product and store it in a variable.

Next, we send the product ID to the backend using the `Z.Request.action` method:
```html
<?php return ["body" => function ($opt) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($opt["products"] as $product) { ?>
                <tr>
                    <td><?= $product["name"] ?></td>
                    <td><?= $product["price"] ?></td>
                    <td>
                        <?php if($opt["user"]->checkPermission("library.edit")) { ?>
                            <button class="delete-product" data-id="<?= $opt['id'] ?>">">Delete</button>
                        <?php } else { ?>
                            <i>No Permissions</i>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <?php if($opt["user"]->checkPermission("library.edit")) { ?>
        <script>
            $(".delete-product").click(function() {
                var id = $(this).data("id");

                Z.Request.action('delete-product', {
                    productId: id
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
The `Z.Request.action` method sends the request to the backend, with `delete-product` as the identifier to specify the action to perform. The product `ID` is passed as data to identify the item to delete. The response is then handled by checking if the result is `success`. If so, the page reloads to update the table; otherwise, an error message is displayed.

## How to handle a Request in the Backend?
Backend Requests sended with the `Z.Request.action` method, will always land on the Action, we are currently in with the view.  
To handle now the request we need to check if the action in our controller is set to our identifier of our request (`delete-product`).  
We are doing it like this:
```php
<?php
    class ShopController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $req->checkPermission("shop.view");

            if ($req->isAction("delete-product")) {

            }

            $products = $req->getModel("Shop")->getProducts();

            return $res->render("shop/shopping_cart", [
                "products" => $products
            ]);
        }
    }
?>
```
To handle the backend request, we first check if the request matches the `delete-product` identifier using `$req->isAction("delete-product")`. This ensures we are processing the correct action.

Next, we secure the request and retrieve the `productId` sent from the frontend:
```php
<?php
    class ShopController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $req->checkPermission("shop.view");

            if ($req->isAction("delete-product")) {
                $req->checkPermission("shop.delete");
                $productId = $req->getPost("productId");

            }

            $products = $req->getModel("Shop")->getProducts();

            return $res->render("shop/shopping_cart", [
                "products" => $products
            ]);
        }
    }
?>
```
Here, `$productId = $req->getPost("productId")` retrieves the product ID from the request data.  
We then delete the product by calling the `deleteProduct` method in the `ShopModel` and passing the `productId` as a parameter:

```php
<?php
    class ShopController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $req->checkPermission("shop.view");

            if ($req->isAction("delete-product")) {
                $req->checkPermission("shop.delete");
                $productId = $req->getPost("productId");

                $req->getModel("Shop")->deleteProduct($productId);
            }

            $products = $req->getModel("Shop")->getProducts();

            return $res->render("shop/shopping_cart", [
                "products" => $products
            ]);
        }
    }
?>
```

Finally, we send a response back to the frontend. The framework provides several response methods, such as:  

- `$res->success()`  Indicates the request was successful.
- `$res->error()` Indicates the request failed.

In this case, since the request is successful, we use `$res->success()` and return immediately to prevent further code execution:

```php
<?php
    class ShopController extends z_controller {

        public function action_list(Request $req, Response $res) {
            $req->checkPermission("shop.view");

            if ($req->isAction("delete-product")) {
                $req->checkPermission("shop.delete");
                $productId = $req->getPost("productId");

                $req->getModel("Shop")->deleteProduct($productId);
                return $res->success();
            }

            $products = $req->getModel("Shop")->getProducts();

            return $res->render("shop/shopping_cart", [
                "products" => $products
            ]);
        }
    }
?>
```

This ensures a clear response is sent to the frontend, confirming whether the deletion was successful, and prevents unnecessary processing after the response. For additional response options, refer to the [REST API documentation](../core-features/rest-api.md).

## Why Set `active = 0` Instead of Deleting the Product from the Database?
The `deleteProduct` function in our `ShopModel` is implemented as follows:
```php
public function deleteProduct($productId) {
    $sql = "UPDATE `product`
            SET `active` = 0
            WHERE `id` = ?";
    $this->exec($sql, "i", $productId);
}
```
Instead of removing the product entirely, this function simply sets the `active` status to `0` in the database. This approach is more prudent because it allows us to retain the data even after a product is "deleted."  

This makes it possible to restore mistakenly deleted products and adds a layer of security for user interactions, for example, in cases where users may post inappropriate content and then try to delete it immediately.  

However, in our `getProducts` function, we make sure to only retrieve items that have an `active` status of `1`, so that "deleted" products are not visible in the regular product listings.

### Summary
- **Z.Request.action**: Sends a request to the backend, requiring an identifier. Additional data can be provided optionally.
- **$req->isAction("")**: Checks whether there is a backend request that needs to be handled.
- **active=0**: It is preferable to set the active status of database entries to 0 rather than deleting them permanently.

This guide demonstrates how to implement backend requests for our website, enabling efficient data management directly from the frontend.

## Next Guide
In the next guide, we will learn how to add a form to our website. This will allow us to easily and securely insert data into a database.

