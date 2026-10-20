# CSRF Protection
Since version **1.4.0**, the framework defends every state-changing request against Cross-Site Request Forgery with a stateless double-submit cookie.

Every request ensures a `z_csrf` cookie holding a random 40-character token: a fresh one is issued when the browser sends none, otherwise the existing one is reused. Z.js reads that cookie and echoes it back as an `X-CSRF-Token` header on every request it sends, so [`Z.Forms` and `Z.Request`](../frontend-integration/backend-requests.md) need no change. The server compares the two and answers a mismatch with `403` and a JSON error body:

```json
{
    "error": {
        "code": 403,
        "message": "csrf token mismatch"
    }
}
```

The cookie travels automatically on any request the browser makes, so on its own it proves nothing. The header does: setting it requires reading the cookie, and only code from the same origin is allowed to do that. An attacker's page can trigger a request, but cannot fill in the header.

## When the check runs
The Router issues the cookie on every request. Before the middlewares and the action of a route run, the token is compared for every request that

1. uses a method other than `GET`, `HEAD` or `OPTIONS`, and
2. a cross-site page could send without a CORS preflight.

The second condition skips requests that only a script on an allowed origin, or a client outside the browser, can send:

| Skipped when | Typical sender |
| ------------ | -------------- |
| The `Content-Type` is anything but `application/x-www-form-urlencoded`, `multipart/form-data` or `text/plain` | JSON APIs, webhooks posting JSON |
| An `Authorization` header carries a scheme other than `Basic`, `Digest`, `NTLM` or `Negotiate` | `Bearer` tokens of SPAs, mobile apps and services |

The four schemes stay checked because the browser attaches cached credentials of these schemes by itself, to cross-site requests too. A request without a body has no `Content-Type` and is checked as well.

Safe methods are never checked, so an action must not change state on `GET`.

## Opting out
An endpoint that a browser session never calls with its own token, like form-encoded API-key authentication or a `navigator.sendBeacon()` target, opts out on its route or group:

```php
Route::post('/hook', [HookController::class, 'action_receive'])->withoutCsrf();

Route::group('/api', function() {
    Route::post('/orders', [ApiController::class, 'action_orders']);
})->withoutCsrf();
```

The opt-out is inherited exactly like a [group middleware](routing.md#middleware-on-groups): it covers every route declared inside the group and every convention path below its prefix. A group without routes is therefore enough for convention controllers:

```php
Route::group('/click/interaction')->withoutCsrf();
```

Such a group only reaches convention paths. An explicit route opts out where it is declared, either directly or by being declared inside the group.

An opted-out endpoint has to authenticate its caller by something other than the session cookie.

## Raw HTML forms
A raw HTML form cannot set a header, so it carries the token in a hidden `_csrf` field instead, which the check accepts in place of the header. `CSRF::field()` renders that input from the cookie the Router has already issued:

```blade
<form method="post">
    {!! \ZubZet\Framework\Security\CSRF::field() !!}
    ...
</form>
```

## Interaction with `login_scope_allow_subdomains`
With `login_scope_allow_subdomains = true` the **session** cookie is issued with a `Domain=.example.com` attribute so it is shared across sibling subdomains for single sign-on.

The `z_csrf` cookie deliberately does **not** follow that setting. It is always issued host-only, without any `Domain` attribute, so `other.example.com` cannot read the token belonging to `app.example.com`. This is intentional: subdomain sharing neutralises the browser's default `SameSite=Lax` protection for sibling hosts, which is exactly the case the CSRF token has to cover.

The practical consequence is that each host maintains its own token. A page served from one subdomain cannot submit a `Z.Forms` request to another subdomain.

## Configuration
There is nothing to configure. The framework's cookies (`z_csrf`, the `z_login_token` session cookie and the maintenance bypass cookie) carry the `Secure` attribute whenever the `host` setting starts with `https://`, so the browser only ever sends them back over HTTPS. A deployment whose `host` is `http://` gets them without the attribute, because a `Secure` cookie is never stored on a plain `http://` origin: the token would never return, every mutating request would be answered with `403`, and no login would stick.

Keep `host` on `https://` in production: without `Secure`, a single `http://` request to the same domain leaks the session and the token in cleartext to anyone on the network path.

## Custom AJAX
Requests built by hand with `fetch()` or `$.ajax()` bypass Z.js and have to attach the header themselves, otherwise they receive a `403`. Ask Z.js for the token:

```js
fetch(url, {
    method: "POST",
    headers: { "X-CSRF-Token": Z.Request.csrfToken() },
    body: data,
});
```

`Z.Request.csrfToken()` returns `null` when the browser holds no cookie. On a page the framework rendered that only happens when the cookie was rejected, which means the `host` setting says `https://` while the page is actually served over plain `http://`.

## When a legitimate request is rejected
Z.js reads the cookie at the moment it sends, so header and cookie always agree and a well-configured deployment never sees a `403` for its own requests. A rejection therefore signals a deployment problem, not a user error, and Z.js deliberately gives it no special treatment: `Z.Request` handlers are simply never called and `Z.Forms` only re-enables its button, so look at the network tab. The usual causes:

| Scenario | Cause |
| -------- | ----- |
| `host` says `https://` but the app is served over plain `http://` | The `Secure` cookie is never stored, no header can be sent; make `host` match the real scheme |
| Cookie missing at send time | Cleared by the user, blocked by a browser policy, or a tab left open past the 30-day lifetime; the next response issues a new one, so a page reload recovers |
| Header stripped in transit | A proxy or WAF that drops unknown `X-` headers; allow `X-CSRF-Token` |
| Page on one subdomain posting to another | The token is host-only by design, and a cross-origin custom header also needs CORS; each host has to serve its own pages |
