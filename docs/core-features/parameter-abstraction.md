# Parameters, post, get, file and cookies
## URL Parameters
When using virtual links, parameters as “subfolders” is a great way of transfering values, that are also very readable. See the following link schema as an example of what is meant by parametres within the virtual link: `controller/action/param0/param1/param2/...`
### Read the parameters using code
To read the virtual url parameters use `$req->getParameters`. It works using an offset and a length, but can also use a value to compare against if the length is one.
### Examples
Example URL:<br>
`www.yourwebsite.com/{controller}/{action}/a/b/c`

Get the first parameter:<br>
`$req->getParameters(0, 1);`<br>
Returns: "a"

Get all the parameters after the second one:<br>
`$req->getParameters(1);`<br>
Returns: ["b", "c"]

Check if the first parameter is "test":<br>
`if($req->getParameters(0, 1, "test")) {` ...<br>
Returns: false

Get the first parameter when using a fallback action<br>
`$req->getParameters(-1, 1);`<br>
Returns "action"


### Code Example
```php
// Example execution `{root}/{controller}/employee/view/2`
public function action_employee(Request $req, Response $res) {

    // Checks if the first URL parameter (offset 0, length 1) is "view"
    if ($req->getParameters(0, 1, "view")) {

        // Retrieves the second URL parameter (offset 1, length 1), which is the employeeId
        $employeeId = $req->getParameters(1, 1);

        return $res->render("employee/employee_view.php", [
            "employee" => $req->getModel("Employee")->getEmployeeById($employeeId)
        ]);
    }

    return $res->render("employee/list.php");
}
```

## GET, POST, COOKIES, FILE as a method
The framework includes some extra functionality when it comes to the above mentioned features and you should use the framework instead of the traditional way. This is because the framework employes extra filtering and processing methods as well as error handling already for you.

### Getting POST and GET parameters
`$req->getGet` and `$req->getPost` are methods to get form parameters. These also enable to set default parameters if some are not set.<br>
**Note:** Post parameters get decoded automatically if their values have a special prefix like `<#decb64#>` or `<#decURI#>`. This decoding allows to transmit special characters.

### Cookies
`$req->getCookie` gets a cookie. It has a second parameter to set a default if the cookie is not set.

`$res->setCookie` has the same parameters as the native `setcookie` function of php. It should be used, because in the future may more logic build into the framework that deals with cookies.

`$res->unsetCookie` is an advanced method to remove cookies from the client.

### File
`$req->getFile` uses `$_FILE` like `$req->getPost` uses `$_POST`.
