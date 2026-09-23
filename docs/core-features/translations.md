# Translations

Texts are translated with [`symfony/translation`](https://symfony.com/doc/current/translation.html). An application keeps its texts in catalogues, one file per domain and locale, and looks them up with `__()`. The framework ships catalogues of its own for the admin panel and the form texts of `Z.js`, in English and German.

---

## Catalogues

Catalogues live in `app/Translations/` and are named `{domain}.{locale}.{format}`:

```
app/Translations/
    messages.en.json
    messages.de.json
    messages.de_CH.json
    mails.de.php
```

The formats are `json`, `php`, `csv` and `ini`. `messages` is the default domain. A region gets its own catalogue with an underscore, `de_CH`, and only needs the keys it spells differently: whatever it leaves out falls back to `de`.

Nested keys are joined with dots:

```json
{
    "dashboard": {
        "welcome": "Welcome back, {name}!"
    }
}
```

[Modules](../advanced-features/modules.md) ship catalogues the same way. An application key beats the same key of a module, and a module key beats the framework's. So redefining a single framework text, say `form.error_required`, only takes that key in the application catalogue.

---

## Translating

```php
__("dashboard.welcome", ["{name}" => $user->email()]);
```

**Syntax:** `__(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string`

* **$id**: The message id, e.g. `dashboard.welcome`. An id no catalogue defines is returned unchanged.
* **$parameters**: Placeholder values, keyed the way the catalogue spells the placeholder, braces included.
* **$domain**: The catalogue domain, `messages` when omitted.
* **$locale**: A locale override, the [active locale](#locale) when omitted.

In a Blade view, `{{ __("dashboard.welcome", ["{name}" => $name]) }}` escapes the result like any other echo.

An application that already defines a global `__()`, for example through another library, keeps its own: the framework only declares the helper when no function of that name exists yet.

---

## Locale

The active locale is picked in this order:

1. The locale of the logged in user, `z_user.locale_bcp_47`, e.g. `de`.
2. The best entry of the request's `Accept-Language` header that a catalogue covers. The entries are tried by their quality, and each is matched against the locales of the loaded catalogues with the RFC 4647 lookup, so `de-AT` finds `de` when there is no `de_AT` catalogue. This takes the PHP `intl` extension.
3. The first of `fallback_locales`.

| Setting | Default | Meaning |
| ------- | ------- | ------- |
| `fallback_locales` | `en` | Comma separated locales. The first is the locale of last resort, and a message missing in the active locale is looked up in them in order. |

The parsed header is available on its own as well, see [`$req->acceptLanguage()`](parameter-abstraction.md#accept-language).

---

## Frontend Texts

The form texts in `Z.Lang`, like *Submit*, *Saved!* and the validation errors, come from the `form.*` keys of the catalogue:

| Key | Text |
| --- | ---- |
| `form.submit`, `form.saved`, `form.unsaved` | Submit button and save hints |
| `form.choose_file` | File input |
| `form.error_required`, `form.error_filter`, `form.error_length`, `form.error_unique`, `form.error_exist`, `form.error_range`, `form.error_file_to_big` | Validation errors |

`<x-zubzet::head/>` writes them into `Z.Lang` for the active locale, so an application translates them like its own texts.
