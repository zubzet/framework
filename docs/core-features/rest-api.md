# REST Responses
## What is this?
As the client and the server are completely separated, they need to have a way communicated. It is basically a standardized way of transfering, in our case JSON data, from the server to the client.

## How to use this?
The REST response pipeline lives in `z_rest.php`, but you'll typically reach for one of the response helpers instead:

| Call                      | Purpose                                                                     |
| ------------------------- | --------------------------------------------------------------------------- |
| `$res->generateRest`      | Sends a REST payload wrapped in a `meta` envelope and exits by default      |
| `$res->generateRestError` | Sends a REST error envelope                                                 |
| `$res->error`             | Convenience for `generateRest` with `result=error`                          |
| `$res->success`           | Convenience for `generateRest` with `result=success`                        |
| `$res->json`              | Sends a raw JSON payload (no `meta` wrapper); does not exit                 |

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

### json
Use `json` when you need to send a raw JSON payload without the REST `meta` wrapper. It sets `Content-Type: application/json` and echoes `json_encode($data)`. Unlike the other helpers above it does not exit — call `exit` yourself if you need to stop the request here. `JSON_THROW_ON_ERROR` is always applied, so unencodable values raise a `JsonException`.

```php
$res->json([
    "ok" => true,
    "items" => [1, 2, 3],
]);
```
```json
{"ok":true,"items":[1,2,3]}
```

Pass extra `json_encode` options as the second argument:
```php
$res->json($data, JSON_PRETTY_PRINT);
```