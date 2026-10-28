# CSRF Protection
Since version **1.3.0**, the framework defends every request against Cross-Site Request Forgery, whatever its method.

A forged request is one the browser of a logged-in user sends because a page of another site told it to: the browser attaches the session cookie by itself. The defense reads only the headers the browser sets. There is no token, no cookie and no hidden form field, so forms, `Z.Request`, `Z.Forms` and hand-written `fetch()` calls need nothing extra.

!!! warning "Links from other sites are rejected"
    `GET`, `HEAD` and `OPTIONS` are checked like every other method. A link on another site, in a webmail or a chat, an OAuth or payment callback and an image embedded elsewhere get a `403`, unless their origin is trusted. See [Limitations](#limitations).

## The headers
| Header | Set by | Says |
| ------ | ------ | ---- |
| `Sec-Fetch-Site` | Current browsers, to `https://` and `localhost` only | How the page that started the request relates to its target: `same-origin`, `same-site`, `cross-site`, or `none` when no page started it (a typed URL, a bookmark, a link from a desktop application) |
| `Origin` | Browsers, on `fetch()`, its CORS preflight and every method but `GET` and `HEAD` | The origin of that page, or `null` when the browser hides it |

A page can make its `Origin` `null`, but it cannot forge any of these headers.

## When the check runs
The router checks every request before the route files load, so no route, middleware or controller runs for a rejected one. This covers explicit routes, convention paths, the framework's routes under `/_zubzet`, the 404 page and the login that `checkPermission()` shows. There is no exception per method or route.

A rejected request gets `403` and a JSON body, which is logged as a `REST_ERROR` warning as well:

```json
{ "error": { "code": 403, "message": "csrf check failed" } }
```

## How a request is judged
**The source** is the `Origin` header, lowercase. Without it, or with `Origin: null`, the source is unknown. The `Referer` is not looked at.

`csrf_allow` sets which `Sec-Fetch-Site` values pass on their own: `same-origin` lets `same-origin` and `none` through, `same-site` lets `same-site` through as well, `any` lets every value through (a public API for browsers on other sites; only `csrf_blocked_origins` still rejects). Without a cookie session nothing is there to forge, but with one, `any` switches the protection off.

1. **Neither `Sec-Fetch-Site` nor `Origin` is sent:** passes. This is curl, a server or a webhook, and nothing else is checked.
2. **`Sec-Fetch-Site` is present and not let through by `csrf_allow`:** passes only when the source is listed in `csrf_trusted_origins`.
3. **Anything else:** passes unless the source is listed in `csrf_blocked_origins`.

Both headers are compared lowercase and trimmed. A request with an `Origin` but no `Sec-Fetch-Site` (an old browser, plain `http://`) therefore only meets the blocklist. A source listed in both lists passes in step 2 and is rejected in step 3, depending on whether `csrf_allow` lets its `Sec-Fetch-Site` through.

| Request to `https://app.example.com` from | `Sec-Fetch-Site` | Default | `csrf_allow = same-site` |
| ----------------------------------------- | ---------------- | ------- | ------------------------ |
| a page of `https://app.example.com` | `same-origin` | passes | passes |
| a typed URL, a bookmark | `none` | passes | passes |
| a page on `https://www.example.com` | `same-site` | rejected | passes |
| a page on `https://partner.com`, listed in `csrf_trusted_origins` | `cross-site` | passes | passes |
| a link on `https://partner.com` that sends no `Origin` | `cross-site` | rejected | rejected |
| a page on `https://evil.com`, a search result, a webmail | `cross-site` | rejected | rejected |
| curl, a server, an old browser | absent | passes | passes |

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

`csrf_trusted_origins` only lets the request through. Reading the answer, or sending JSON or custom headers, needs CORS on top. The CORS preflight is checked itself, so an origin that calls the application with `fetch()` has to be listed.

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
| 7 | Every subdomain may, except `x` | `csrf_allow = same-site`<br>`csrf_blocked_origins = https://x.example.com` | |
| 8 | The application runs on a subdomain, the parent host may | `csrf_trusted_origins = https://example.com` | `www.example.com` is another origin |
| 9 | The application runs on a subdomain, another subdomain may | `csrf_trusted_origins = https://b.example.com` | |
| 10 | The nested subdomain `x.y` may, all others not | `csrf_trusted_origins = https://x.y.example.com` | Neither `y.example.com` nor `a.x.y.example.com` is covered |
| 11 | The same host may on port 3000, not on port 4000 | `csrf_trusted_origins = https://app.example.com:3000` | Under `csrf_allow = same-site`, use `csrf_blocked_origins = https://app.example.com:4000` instead |

## Limitations
- **No `Sec-Fetch-Site`.** Old browsers (Safari before 16.4) and every browser over plain `http://` to a host other than `localhost` send none, so only `csrf_blocked_origins` applies to them, and only when they send an `Origin`; everything else passes. Such browsers are protected only by the `SameSite=Lax` session cookie. Serve the application over `https://`.
- **Ports are not part of a site.** A browser sends `same-site` from `https://app.example.com:4000` to `https://app.example.com`, so under `csrf_allow = same-site` every port of every subdomain passes unless blocked.
- **`csrf_allow = any`** switches the protection off for every site; only `csrf_blocked_origins` still applies. Use it for an API that has no cookie session.
- **No whole sites or wildcards.** An entry is one exact origin. `https://*.example.com` or a bare site cannot be listed; list each origin, or use `csrf_allow = same-site` for the own site.
- **Links and embeds from other sites.** Every link, redirect, `<img>` or `<script>` from another site to a PHP route gets a `403` unless its source is trusted: search results, webmails, chats, OAuth and SAML callbacks, payment returns. The links in the application's own mails are rejected when opened in a webmail; a desktop mail client sends `none` and passes. Most browsers send no `Origin` on such a link, so it has no known source and is rejected.
- **Files served by Apache directly** (everything in the webroot that does not go through `index.php`, such as images, scripts and uploads) are not checked.

## When a legitimate request is rejected
`Z.Request` and `Z.Forms` give a `403` no special treatment, so look at the network tab.

| Case | Cause |
| ---- | ----- |
| Another site | The request comes from another site, or from another subdomain or port under `csrf_allow = same-origin`. List its origin in `csrf_trusted_origins`, or use `csrf_allow = same-site` when every subdomain is yours. A link without `Origin` cannot be trusted |
| Invalid `csrf_allow` | A request with a browser header throws an error (`500`) until the setting is `same-origin`, `same-site` or `any` |
| Blocked origin | The source is listed in `csrf_blocked_origins` |
