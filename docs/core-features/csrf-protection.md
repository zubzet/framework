# CSRF Protection
Since version **1.4.0**, the framework defends [`Z.Forms` and `Z.Request`](../frontend-integration/backend-requests.md) against Cross-Site Request Forgery with a stateless double-submit cookie.

Every request ensures a `z_csrf` cookie holding a random 40-character token: a fresh one is issued when the browser sends none, otherwise the existing one is reused. Z.js reads that cookie and echoes it back as an `X-CSRF-Token` header on every request it sends. The server compares the two and answers a mismatch with `403` and a JSON error body:

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
Constructing a `Csrf` object issues the cookie and runs the check. The Router does that once per request.

The token is compared when both conditions hold:

1. The request method is **not** `GET`, `HEAD` or `OPTIONS`.
2. The request carries a ZubZet marker - `isFormData` (sent by `Z.Forms`) or `_zReq` (sent by `Z.Request.action()` / `Z.Request.root()`).

Anything else passes through unchecked.

The marker travels in the request body, so an attacker can leave it out to opt out of the check. That is harmless wherever reaching the code also requires the marker, because dropping it costs the attacker the action itself:

- `$req->hasFormData()` needs `isFormData`.
- `$req->isAction()` needs `_zReq`.

It is not harmless for actions that run on the plain POST fields without either predicate. Those construct their own `Csrf` with `enforce: true`, which skips the marker test and always demands the header:

```php
new Csrf(enforce: true);
```

## Interaction with `login_scope_allow_subdomains`
With `login_scope_allow_subdomains = true` the **session** cookie is issued with a `Domain=.example.com` attribute so it is shared across sibling subdomains for single sign-on.

The `z_csrf` cookie deliberately does **not** follow that setting. It is always issued host-only, without any `Domain` attribute, so `other.example.com` cannot read the token belonging to `app.example.com`. This is intentional: subdomain sharing neutralises the browser's default `SameSite=Lax` protection for sibling hosts, which is exactly the case the CSRF token has to cover.

The practical consequence is that each host maintains its own token. A page served from one subdomain cannot submit a `Z.Forms` request to another subdomain.

## Configuration
The cookie carries the `Secure` attribute by default, so the browser only ever sends it back over HTTPS. Production deployments need no configuration.

A `Secure` cookie is ignored on plain `http://` origins, which means the token never returns and every mutating request is answered with `403`. Browsers treat `localhost` as a secure context, so local development over `http://localhost` is unaffected. Only a setup served over plain HTTP under a real hostname - a LAN address, a staging box without a certificate - has to turn the attribute off in `z_settings.ini`:

```ini
csrf_secure = false
```

Do not ship that setting to production: without `Secure`, a single `http://` request to the same domain leaks the token in cleartext to anyone on the network path.

## Limits of this release
The check is scoped to `Z.Forms` and `Z.Request` so existing applications keep working without code changes. That leaves gaps:

| Not covered | Consequence |
| ----------- | ----------- |
| Raw `<form method="post">` in application code | No marker, so no check unless the action constructs `new Csrf(enforce: true)` |
| Actions reading `getPost()` without `hasFormData()` / `isAction()` | Nothing forces the marker, so nothing forces the check |
| State-changing actions reachable via `GET` | Safe methods are never checked |
| Custom `fetch()` / `$.ajax()` outside `Z.Request` | Must attach the header itself, otherwise it receives a `403` |

For custom AJAX, ask Z.js for the token and set the header yourself:

```js
fetch(url, {
    method: "POST",
    headers: { "X-CSRF-Token": Z.Request.csrfToken() },
    body: data,
});
```

`Z.Request.csrfToken()` returns `null` when the browser holds no cookie. On a page the framework rendered that only happens when the cookie was rejected, which is the `csrf_secure` case described above.
