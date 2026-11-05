# CSRF Protection
Since version **1.3.0**, the framework defends every request that can change state against Cross-Site Request Forgery.

A forged request is one the browser of a logged-in user sends because a page of another site told it to: the browser attaches the session cookie by itself. The defense reads only the headers the browser sets. There is no token, no cookie and no hidden form field, so forms, `Z.Request`, `Z.Forms` and hand-written `fetch()` calls need nothing extra.

!!! warning "`GET`, `HEAD` and `OPTIONS` are not checked"
    Links, redirects and embeds from other sites keep working, so do OAuth and payment callbacks and the links of your own mails opened in a webmail. In return, an action that changes state on a `GET` is unprotected until it opts back in, see [Protecting a normally safe method](#protecting-a-normally-safe-method).

## The headers
| Header | Set by | Says |
| ------ | ------ | ---- |
| `Sec-Fetch-Site` | Current browsers, to `https://` and `localhost` only | How the page that started the request relates to its target: `same-origin`, `same-site`, `cross-site`, or `none` when no page started it (a typed URL, a bookmark, a link from a desktop application) |
| `Origin` | Browsers, on `fetch()`, its CORS preflight and every method but `GET` and `HEAD`; never on a link or another page navigation by `GET` | The origin of that page, or `null` when the browser hides it |

A page can make its `Origin` `null`, but it cannot forge any of these headers.

## When the check runs
The router checks every request before the route files load, so no route, middleware or controller runs for a rejected one. This covers explicit routes, convention paths, the framework's routes under `/_zubzet`, the 404 page and the login that `checkPermission()` shows. `GET`, `HEAD` and `OPTIONS` pass the router unchecked; there is no other exception per method or route.

### Protecting a normally safe method
`GET`, `HEAD` and `OPTIONS` are skipped by `CSRF::verify()` on the assumption that they never change state. An action that breaks that assumption, like a `GET` logout, has to opt back in: call `$req->csrfBlockAdditionalMethod()` (or `CSRF::verify("GET")` directly) before doing anything state-changing. Without arguments it covers `GET`; pass the methods explicitly to cover others, like `csrfBlockAdditionalMethod("GET", "HEAD")`, since a `HEAD` request runs the same action and a page can send one with the session cookie. Method names are case insensitive. Passing a method name there removes it from the safe-method exemption for that call only, so the usual checks (`Sec-Fetch-Site`, `Origin`, `csrf_allow` and the trusted/blocked origin lists) run against it too. Like `checkPermission()`, `csrfBlockAdditionalMethod()` returns nothing: a rejected request gets the `403` page and the script ends, while `CSRF::verify()` only returns the result.

```php
public function action_logout($req, Response $res) {
    // Logout is a GET action, but still state-changing, so it needs
    // CSRF protection too despite GET normally being exempt as safe.
    // HEAD runs the same action and a page can send it with cookies
    $req->csrfBlockAdditionalMethod("GET", "HEAD");

    return $res->logout();
}
```

A browser sends no `Origin` on a link, so a `GET` that opts back in only passes from the application's own origin or from no page at all (a typed URL, a bookmark), whatever `csrf_allow` and `csrf_trusted_origins` say. That suits a logout; an action that other sites have to link to cannot be protected this way and should take a `POST` instead.

A rejected request is answered by the `403` page of the error controller (`error/403`), the same one `checkPermission()` shows.

## How a request is judged
**The source** is the `Origin` header, lowercase. Without it, or with `Origin: null`, the source is unknown. The `Referer` is not looked at.

`csrf_allow` sets which `Sec-Fetch-Site` values pass on their own: `same-origin` lets `same-origin` and `none` through, `same-site` lets `same-site` through as well, `any` lets every value through (a public API for browsers on other sites; only `csrf_blocked_origins` still rejects). Without a cookie session nothing is there to forge, but with one, `any` switches the protection off.

1. **`GET`, `HEAD` or `OPTIONS`, not opted back in** (see [Protecting a normally safe method](#protecting-a-normally-safe-method)): passes.
2. **Neither `Sec-Fetch-Site` nor `Origin` is sent:** passes. This is curl, a server or a webhook, and nothing else is checked.
3. **The source is unknown and `csrf_allow` is not `any`:** passes only with `Sec-Fetch-Site` `same-origin` or `none`. Neither list can match an unknown source, so a page that hides its `Origin` (`no-referrer`, a sandboxed iframe) cannot slip past `csrf_blocked_origins` this way.
4. **`Sec-Fetch-Site` is present and not let through by `csrf_allow`:** passes only when the source is listed in `csrf_trusted_origins`.
5. **Anything else:** passes unless the source is listed in `csrf_blocked_origins`.

Both headers are compared lowercase and trimmed. A request with an `Origin` but no `Sec-Fetch-Site` (an old browser, plain `http://`) therefore only meets the blocklist. A source listed in both lists passes in step 4 and is rejected in step 5, depending on whether `csrf_allow` lets its `Sec-Fetch-Site` through.

A `POST` (or any other checked request) to `https://app.example.com` from:

| Source | `Sec-Fetch-Site` | Default | `csrf_allow = same-site` |
| ------ | ---------------- | ------- | ------------------------ |
| a page of `https://app.example.com` | `same-origin` | passes | passes |
| a typed URL, a bookmark | `none` | passes | passes |
| a page on `https://www.example.com` | `same-site` | rejected | passes |
| a page on `https://www.example.com` that hides its `Origin` (`no-referrer`) | `same-site` | rejected | rejected |
| a page on `https://partner.com`, listed in `csrf_trusted_origins` | `cross-site` | passes | passes |
| a page on `https://partner.com` that hides its `Origin` | `cross-site` | rejected | rejected |
| a page on `https://evil.com` | `cross-site` | rejected | rejected |
| curl, a server, an old browser sending an `Origin` | absent | passes | passes |

A `GET`, `HEAD` or `OPTIONS` passes in every row, unless the action opts back in.

## Settings
| Setting | Default | Meaning |
| ------- | ------- | ------- |
| `csrf_allow` | `same-origin` | `same-origin`, `same-site` or `any`, case insensitive; any other value throws an error |
| `csrf_trusted_origins` | empty | Comma separated origins that pass although `csrf_allow` does not let their `Sec-Fetch-Site` through |
| `csrf_blocked_origins` | empty | Comma separated origins that are rejected although `csrf_allow` lets their `Sec-Fetch-Site` through, or it is absent |

```ini
csrf_allow = same-origin
csrf_trusted_origins = https://partner.com, https://docs.example.com
csrf_blocked_origins =
```

An entry is compared as written, only lowercased and trimmed, like the `Origin` header. Write it exactly as the browser sends the `Origin`: no path, no trailing slash, and no port unless it is not the default one (`80` for `http`, `443` for `https`). `https://partner.com:443` therefore never matches, while `http://partner.com`, `https://partner.com:8443` and `https://www.partner.com` are other origins than `https://partner.com`. Write internationalized hosts in their `xn--` form, as the browser sends them.

`csrf_trusted_origins` only lets the request through. Reading the answer, or sending JSON or custom headers, needs CORS on top. The CORS preflight is an `OPTIONS` and passes unchecked, the request that follows it is checked, so an origin that calls the application with `fetch()` has to be listed.

Only use `csrf_allow = same-site` when every subdomain of the site is yours, future and forgotten ones included: a subdomain hosting content of others (uploads, a bucket, one per customer), one with an XSS hole, or one whose DNS points to a cancelled service can then act with the sessions of your users. Block the risky ones with `csrf_blocked_origins`, or stay on `same-origin` and list the trusted ones.

## Configuration by scenario
The application runs on `https://app.example.com`.

| # | Scenario | Configuration | Notes |
| - | -------- | ------------- | ----- |
| 1 | The same application runs on several subdomains | Nothing | Each subdomain passes its own requests (`same-origin`); a request from one to another is `same-site` and rejected unless listed |
| 2 | A bucket on `bucket.example.com` hosts foreign content, every other subdomain may | `csrf_allow = same-site`<br>`csrf_blocked_origins = https://bucket.example.com` | |
| 3 | Subdomain `x` may, subdomain `y` may not | `csrf_trusted_origins = https://x.example.com` | Or `csrf_allow = same-site` with `csrf_blocked_origins = https://y.example.com`, which lets every other subdomain in as well and stops `y` in browsers without `Sec-Fetch-Site` too |
| 4 | Only site `x.com` may | `csrf_trusted_origins = https://x.com` | `www.x.com` is another origin and has to be listed on its own |
| 5 | Only site `x.com` and subdomain `y` may | `csrf_trusted_origins = https://x.com, https://y.example.com` | |
| 6 | Every subdomain may, plus site `x.com` | `csrf_allow = same-site`<br>`csrf_trusted_origins = https://x.com` | |
| 7 | Every subdomain may, except `x` | `csrf_allow = same-site`<br>`csrf_blocked_origins = https://x.example.com` | A page on `x` that hides its `Origin` is rejected too, as is every subdomain doing so |
| 8 | The application runs on a subdomain, the parent host may | `csrf_trusted_origins = https://example.com` | `www.example.com` is another origin |
| 9 | The application runs on a subdomain, another subdomain may | `csrf_trusted_origins = https://b.example.com` | |
| 10 | The nested subdomain `x.y` may, all others not | `csrf_trusted_origins = https://x.y.example.com` | Neither `y.example.com` nor `a.x.y.example.com` is covered |
| 11 | The same host may on port 3000, not on port 4000 | `csrf_trusted_origins = https://app.example.com:3000` | Under `csrf_allow = same-site`, use `csrf_blocked_origins = https://app.example.com:4000` instead |

## Limitations
- **No `Sec-Fetch-Site`.** Old browsers (Safari before 16.4) and every browser over plain `http://` to a host other than `localhost` send none, so only `csrf_blocked_origins` applies to them when they send an `Origin`. A checked request of theirs with a hidden `Origin` (`null`, for example from a page with `no-referrer`) is rejected, even from the application's own pages. Such browsers are otherwise protected only by the `SameSite=Lax` session cookie. Serve the application over `https://`.
- **Ports are not part of a site.** A browser sends `same-site` from `https://app.example.com:4000` to `https://app.example.com`, so under `csrf_allow = same-site` every port of every subdomain passes unless blocked.
- **`csrf_allow = any`** switches the protection off for every site; only `csrf_blocked_origins` still applies. Use it for an API that has no cookie session.
- **No whole sites or wildcards.** An entry is one exact origin. `https://*.example.com` or a bare site cannot be listed; list each origin, or use `csrf_allow = same-site` for the own site.
- **`GET`, `HEAD` and `OPTIONS` are not checked.** An action that changes state on one of them is open to every site until it calls `csrfBlockAdditionalMethod()`. Prefer a `POST` for anything that changes state.
- **A hidden `Origin` is never trusted.** A checked request without a known source passes only as `same-origin` or `none`, whatever `csrf_allow` and the lists say (except under `csrf_allow = any`). A partner that posts from a page with `no-referrer` has to stop hiding its `Origin`.
- **Files served by Apache directly** (everything in the webroot that does not go through `index.php`, such as images, scripts and uploads) are not checked.

## When a legitimate request is rejected
`Z.Request` and `Z.Forms` give a `403` no special treatment, so look at the network tab.

| Case | Cause |
| ---- | ----- |
| Another site | The request comes from another site, or from another subdomain or port under `csrf_allow = same-origin`. List its origin in `csrf_trusted_origins`, or use `csrf_allow = same-site` when every subdomain is yours. A request without `Origin` (a page with `no-referrer`, a link to an action that opts back in) passes only from the application's own origin |
| Invalid `csrf_allow` | A checked request (step 1 does not apply) with a browser header throws an error (`500`) until the setting is `same-origin`, `same-site` or `any` |
| Blocked origin | The source is listed in `csrf_blocked_origins` |
