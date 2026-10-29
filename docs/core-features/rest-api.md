# REST Responses
## What is this?
As the [client](../frontend-integration/backend-requests.md) and the server are completely separated, they need to have a way communicated. It is basically a standardized way of transfering, in our case JSON data, from the server to the client.

## How to use this?
The [REST response pipeline](../api/classes/ZubZet-Framework-Support-Rest.html) is the `Rest` class, but you'll typically reach for one of the response helpers instead:

| Call                      | Purpose                                                                     |
| ------------------------- | --------------------------------------------------------------------------- |
| `$res->generateRest`      | Sends a REST payload wrapped in a `meta` envelope and exits by default      |
| `$res->generateRestError` | Logs a `REST_ERROR` warning, sends only the `error` object and exits (HTTP status stays 200) |
| `$res->error`             | Convenience for `generateRest` with `result=error` and the `message`, an optional payload array is merged like `success` does, exits |
| `$res->success`           | Convenience for `generateRest` with `result=success`, exits; payload keys are merged after `result` and can override it |
| `$res->formErrors`        | Merges all array arguments (others are ignored) into `{"result":"formErrors","formErrors":[...]}` inside the `meta` envelope, exits; each entry holds `name`, `type` and optional `info` |
| `$res->json`              | Sends a raw JSON payload (no `meta` wrapper); does not exit                 |

[generateRest](../api/classes/ZubZet-Framework-Message-Response.html#method_generateRest) takes a payload array whose keys are added after `meta` (a payload key named `meta` replaces it). The second parameter of generateRest called $die determines if the script exits after generating the REST response. The parameter is optional with a default value of true. The pretty-printed JSON is sent without a `Content-Type` header or HTTP status code, unlike `json`.

## Example Calls

### generateRest
```php
$res->generateRest([
    "response" => "RESPONSE",
    "error" => "ERROR"
]);
```
```json
{ "meta": { "endpoint": "REST API", "request": "URL", "timestamp": 9999999 }, "response": "RESPONSE", "error": "ERROR" }
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
Use `json` when you need to [send a raw JSON payload](../api/classes/ZubZet-Framework-Message-Response.html#method_json) without the REST `meta` wrapper. It sets `Content-Type: application/json` (and no status code) and echoes `json_encode($data)`. Unlike the other helpers above it does not exit — call `exit` yourself if you need to stop the request here. `JSON_THROW_ON_ERROR` is always applied, so unencodable values raise a `JsonException`, after the header was already set.

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