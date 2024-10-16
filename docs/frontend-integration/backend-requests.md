# Using Z.Request
The default JavaScript API of this framework does have a function to send asynchronous requests to the server and get data back.
## Front-end
```js
Z.Request.action("add", {
    number1: 3,
    number2: 6
},(res) => {
    alert("The sum of 3 and 6 is " + res.sum);
});
```
`Z.Request.action` takes three parameters. The first one is the identifier of the action as string. The second is a object containing post parameters. The third is a callback which only parameter is the REST response of the server.
## Back-end
```php
if ($req->isAction("sum")) {
    //Here you have access to the server. This is usually the reason we want to use this.
    $sum = $req->getPost("number1") + $req->getPost("number2");
    $res->success([
        "sum" => $sum
    ]);
}
```
[`$req->isAction()`](https://zdoc.zierhut-it.de/classes/Request.html#method_isAction) detects if this Request was initiated by an async action call with a specified identifier. Note that these actions are not the same as the ones in the controller. They work a level higher. [`$res->generateRest`](https://zdoc.zierhut-it.de/classes/Response.html#method_generateRest) will create a parsable answer for the client.
