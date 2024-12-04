# REST Responses
## What is this?
As the client and the server are completely separated, they need to have a way communicated. It is basically a standardized way of transfering, in our case JSON data, from the server to the client.

## How to use this?
There is a file called `z_rest.php`, which handles all the REST responses. You'll probably not be using this as another layer of abstraction exists within the response class. There are two functions regarding this topic. 

| Call                      | zdoc                                                                              |
| ------------------------- | --------------------------------------------------------------------------------- |
| `$res->generateRest`      | [Here](https://zdoc.zierhut-it.de/classes/Response.html#method_generateRest)      |
| `$res->generateRestError` | [Here](https://zdoc.zierhut-it.de/classes/Response.html#method_generateRestError) |
| `$res->error`             | [Here](https://zdoc.zierhut-it.de/classes/Response.html#method_generateRestError) |
| `$res->success`           | [Here](https://zdoc.zierhut-it.de/classes/Response.html#method_generateRestError) |

You can also just use generateRest for errors. If the key result is set with the value error, it is automatically converted into a REST Error. The second parameter of generateRest called $die determines if the script exits after generating the REST response. The parameter is optional with a default value of true.

## Example Calls

### generateRest
```php
$res->generateRest([
    "response" => "RESPONSE",
    "error" => "ERROR"
]);
```
```json
{ "meta": { "endpoint": "REST API", "request": "URL", "timestamp": 9999999 }, "response": "hehe", "error": "ERROR" }
```

### generateRestError
```php
$res->generateRestError(404, "MESSAGE");
```
```json
{ "error": { "code": 404, "message": "MESSAGE" } }
```

### success
```php
$res->success([
    "information" => "MESSAGE"
]);
```
```json
{ "meta": { "endpoint": "REST API", "request": "URL", "timestamp": 9999999 }, "result": "success", "information": "MESSAGE" }
```

### error
```php
$res->error("MESSAGE");
```
```json
{ "meta": { "endpoint": "REST API", "request": "URL", "timestamp": 9999999 }, "result": "error", "message": "MESSAGE" }
```