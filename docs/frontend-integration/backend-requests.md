# Using Z.Request
The default JavaScript API of this framework does have a function to send asynchronous requests to the server and get data back. `Z.Request` is part of Z.js, the framework's bundled frontend script, which is included automatically in every properly set up [layout](../core-features/layouts.md).
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
`Z.Request.action` takes three parameters. The first one is the identifier of the action as string. The second is a object containing post parameters. The third is a callback which only parameter is the [REST response](../core-features/rest-api.md) of the server. The request goes to the current URL and the response text is parsed as JSON before the callback runs; if parsing fails, the response is logged to the console with "Please show this to a developer" and the callback is not called. Failed HTTP requests are not handled. `Z.Request.root(action, subaction, data, handler)` instead posts to `Z.Request.rootPath + action` (for example `login/logout`) with `subaction` as the `action` field. Its `handler` is required, and if a response cannot be parsed the raw response is passed to it. A response sent with `$res->json()` carries `application/json`, which jQuery has already parsed, so `Z.Request.action` and `Z.Forms` cannot parse it again; `Z.Request.root` accepts it.
## Back-end
```php
public function action_list(Request $req, Response $res) {
    if ($req->isAction("delete-employee")) {
        $employeeId = $req->getPost("employeeId");

        $req->getModel("Employee")->deleteById($employeeId);
        return $res->success();
    }

    <!-- Remaining code -->
    return $res->render("employee/list");
}
```
[`$req->isAction()`](../api/classes/ZubZet-Framework-Message-Request.html#method_isAction) detects if this Request was initiated by an async action call with a specified identifier. It compares the POST field `action` (sent by `Z.Request.action()`) loosely, a GET `action` is ignored, and without a posted `action`, `isAction("")` is true because `null == ""`. Note that these actions are not the same as the ones in the controller. They work a level higher. [`$res->generateRest`](../api/classes/ZubZet-Framework-Message-Response.html#method_generateRest) will create a parsable answer for the client.
