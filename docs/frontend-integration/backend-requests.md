# Using Z.Request
The default JavaScript API of this framework does have a function to send asynchronous requests to the server and get data back.
## Front-end
```js
$(".delete-employee").click(function() {
    let selectedEmployee = $(this).data("id");

    Z.Request.action('delete-employee', {
        'employeeId': selectedEmployee
    }, (res) => {
        if(res.result == 'success') {
            location.reload();
            return;
        }
    });
});
```
`Z.Request.action` takes three parameters. The first one is the identifier of the action as string. The second is a object containing post parameters. The third is a callback which only parameter is the REST response of the server.
## Back-end
```php
public function action_list(Request $req, Response $res) {
    if ($req->isAction("delete-employee")) {
        $employeeId = $req->getPost("employeeId");

        $req->getModel("Employee")->deleteById($employeeId);
        return $res->success();
    }

    <!-- Remaining code -->
    return $res->render("employee/list.php");
}
```
`$req->isAction()` detects if this Request was initiated by an async action call with a specified identifier. Note that these actions are not the same as the ones in the controller. They work a level higher. `$res->generateRest` will create a parsable answer for the client.
