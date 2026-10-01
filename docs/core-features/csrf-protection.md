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
Constructing a `CSRF` object issues the cookie and runs the check. The Router does that once per request.

The token is compared when both conditions hold:

1. The request method is **not** `GET`, `HEAD` or `OPTIONS`.
2. The request carries a ZubZet marker - `isFormData` (sent by `Z.Forms`) or `action` (sent by `Z.Request.action()` / `Z.Request.root()`).

Anything else passes through unchecked.

The marker travels in the request body, so an attacker can leave it out to opt out of the check. That is harmless wherever reaching the code also requires the marker, because dropping it costs the attacker the action itself:

- `$req->hasFormData()` needs `isFormData`.
- `$req->isAction()` needs `action`.

It is not harmless for actions that run on the plain POST fields without either predicate. Those call `CSRF::enforce()`, which skips the marker test and always demands the token:

```php
CSRF::enforce();
```

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

## Limits of this release
The check is scoped to `Z.Forms` and `Z.Request` so existing applications keep working without code changes. That leaves gaps:

| Not covered | Consequence |
| ----------- | ----------- |
| Raw `<form method="post">` in application code | No marker, so no check unless the action constructs `CSRF::enforce()` and the form embeds `CSRF::field()` |
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

`Z.Request.csrfToken()` returns `null` when the browser holds no cookie. On a page the framework rendered that only happens when the cookie was rejected, which means the `host` setting says `https://` while the page is actually served over plain `http://`.

## When a legitimate request is rejected
Z.js reads the cookie at the moment it sends, so header and cookie always agree and a well-configured deployment never sees a `403` for its own requests. A rejection therefore signals a deployment problem, not a user error, and Z.js deliberately gives it no special treatment: `Z.Request` handlers are simply never called and `Z.Forms` only re-enables its button, so look at the network tab. Every response issues a fresh cookie, so a page reload recovers all of these:

| Scenario | Cause |
| -------- | ----- |
| `host` says `https://` but the app is served over plain `http://` | The `Secure` cookie is never stored, no header can be sent; make `host` match the real scheme |
| Cookie missing at send time | Cleared by the user, blocked by a browser policy, or a tab left open past the 30-day lifetime |
| Header stripped in transit | A proxy or WAF that drops unknown `X-` headers; allow `X-CSRF-Token` |
| Page on one subdomain posting to another | The token is host-only by design, and a cross-origin custom header also needs CORS; each host has to serve its own pages |
| Cookie planted by a sibling subdomain | A `z_csrf` cookie with a `Domain` attribute that does not match the issued shape is replaced on the next response |
