# Asset Proxy

Since version **1.2.0**, ZubZet serves CSS, JavaScript, fonts, and other static assets through an
**[asset proxy](../api/classes/ZubZet-Framework-Resources-AssetProxy.html)** instead of exposing them as files in your web root. A single internal route resolves
each request against a list of registered source directories, which means framework- and
package-bundled assets no longer have to be copied into every project.

## How assets are requested

The proxy listens on:

```
/_zubzet/asset-proxy/{path}
```

For example, `/_zubzet/asset-proxy/css/bootstrap.min.css` resolves Bootstrap's stylesheet from the
first registered source that contains it.

## Referencing assets in views

Use the `generateResourceLink` helper available on every [view's](views.md) `$opt` array — it builds a correct,
root-relative URL for you:

```blade
@extends($layout)

@section("head")
    <link rel="stylesheet"
          href="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/css/bootstrap.min.css"); ?>">
@endsection

@section("content")
    <script src="<?php $opt["generateResourceLink"]("_zubzet/asset-proxy/js/jquery.min.js"); ?>"></script>
@endsection
```

## Bundled assets

The framework registers these sources automatically at bootstrap, so they work out of the box:

| Asset | Proxy path |
| ----- | ---------- |
| jQuery | `_zubzet/asset-proxy/js/jquery.min.js` |
| Bootstrap (JS/CSS) | `_zubzet/asset-proxy/js/bootstrap.min.js`, `_zubzet/asset-proxy/css/bootstrap.min.css` |
| Font Awesome | `_zubzet/asset-proxy/css/font-awesome/all.min.css` |
| Z.js | `_zubzet/asset-proxy/Z.js` |

These come from the `components/jquery`, `components/bootstrap`, and `components/font-awesome` Composer
packages — no files are copied into your project.

## Registering your own source

To expose an additional directory (for example, assets shipped by one of your own Composer packages),
[register it on the proxy](../api/classes/ZubZet-Framework-Resources-AssetProxy.html#method_registerWebRootSource) with an absolute path and an optional URL prefix:

```php
zubzet()->assetProxy->registerWebRootSource("/var/www/vendor/acme/ui-kit/dist", "ui-kit");
```

The file `dist/app.css` is then available at `/_zubzet/asset-proxy/ui-kit/app.css`. Registration is
lazy — the directory is only touched when a matching request arrives. Sources are checked in
registration order, and the first match wins.

## Module assets and resolution order

Installed [modules](../advanced-features/modules.md) contribute their `webroot/` directories as
sources automatically. The full chain is checked in this order, first match wins:

1. Module `webroot/` sources, in module order
1. Framework assets (`IncludedComponents/assets/`)
1. The framework frontend directory (`Z.js`)
1. Bundled Composer packages (jQuery, Bootstrap, Font Awesome)
1. Sources you register via `registerWebRootSource()`

A module can therefore shadow a framework asset. Your application's own `webroot/` is not part of
this chain: the web server serves it directly, before PHP runs, so it always wins for real files.

## Security

The proxy is designed to serve only files that live inside a registered source:

- **Path traversal is blocked.** Each request is resolved with `realpath()` and rejected unless the
  result stays within the registered source directory, so `../` sequences and symlink escapes cannot
  reach files outside the mount.
- **Only readable, existing files are served**; anything else returns `404`.
- **Source and config extensions are never served.** Requests for `.php`, `.phtml`, or `.ini`
  files return `404` from every source, including ones you register yourself.
- **[Content types are detected](https://github.com/thephpleague/mime-type-detection)** with `league/mime-type-detection`, falling back to
  `application/octet-stream` when the type is unknown.
