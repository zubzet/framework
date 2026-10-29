# The Booter Settings

## What is this?
The booter settings are a way of getting your most basic settings. If you have something like the uploads folder or the database connection, that might be changed by a customer/admin after finishing this project, those values can be stored under your `z_config folder` as a key of `z_settings.ini`.

## How to use it
If you want to use another key, simply create a new key in your `z_settings.ini`. To [recall the value](../api/classes/ZubZet-Framework-Core-CanRetrieveBooterSettings.html#method_getBooterSettings) use `$res->getBooterSettings("key")`. If you don't pass a key, the whole array of values will be returned.

The signature is `getBooterSettings($key = null, $useDefault = true, $default = null)`. The method is public on `ZubZet`, `Request` and `Response` and behaves exactly like the global [`config()`](global-helper-functions.md#config) helper, which proxies to it. An empty `$key` (`null`, `""` or `"0"`) returns the complete settings array, including the database and mail credentials. A missing key returns `null` by default. `$useDefault` is the second positional argument, so `getBooterSettings("key", 5)` does not set a default of `5`: pass it by name (`getBooterSettings("key", default: 5)`). With `$useDefault = false`, a missing key throws an `InvalidArgumentException` with the message `The setting '<key>' does not exist!`.

## Settings file and order of precedence
The file is `z_config/z_settings.ini`, resolved relative to the working directory of the process and held in the built-in key `config_file`. It is read before anything else, so `config_file` itself cannot be changed through the constructor or the environment.

Later layers win:
1. Built-in defaults (paths, `config_file`, `config_automated_file`, framework roots)
2. `z_settings.ini`
3. The `$params` array passed to the `ZubZet` constructor (only for keys that already exist)
4. `CONFIG_*` environment variables (only with `allow_env_config`, see below)
5. The derived `rootFolder` and `root`, computed from `rootDirectory` and `host`
6. The automated settings file (`z_config/z_automated_setting.ini`, key `config_automated_file`), read with a plain `parse_ini_string()` without the `;`/`#` protection of the main file

`rootFolder` is the link prefix `$opt["root"]` in views, the default prefix of `rerouteUrl()` (used by `logout()`) and the path of the maintenance bypass cookie.

`host` is required and has no default. It is the public base URL with scheme and optional port and without a trailing slash, e.g. `http://localhost:4000`, because `root` is built as `host` followed by `rootFolder`, which starts with a slash. `Request::getCurrentURL()` returns `host` plus `REQUEST_URI` (the incoming Host header is ignored) and `getDomain()` returns `host` without scheme, slashes and port. `host` also feeds the `host` and `absRoot` view options, the mail `application_root` and the password-reset link.

Settings can be read and written at runtime as `zubzet()->key` (`zubzet()->settings` is the whole store); a missing key throws, and a write affects only the current request and does not recompute `root` or `rootFolder`. Most settings are read when they are used, but the database connection copies its settings once at boot and a logger reads its settings when its channel is first created, so later writes do not reach them.

## INI syntax
`z_settings.ini` is parsed with `parse_ini_string()` without sections (`[section]` headers are ignored, all keys are flat), but every `;` and `#` is protected before parsing and restored afterwards. A `;` or `#` inside a value is therefore kept literally, so there are no inline comments: a trailing comment becomes part of the value.

```ini
host = https://example.com ; production
```

Here `host` is `https://example.com ; production`.

Values are normalised by `parse_ini_string()`: unquoted `true`, `on` and `yes` become `1`; `false`, `off`, `no`, `none`, `null` and an empty value become an empty string; numbers stay strings. A double-quoted value is not normalised, so `"true"` stays the literal text `true`, which matters for settings compared with the string `true`.

A line that starts with `;` or `#` is not a real comment either. It only survives as long as it parses as an ini line: with an `=` in it (`; debug = on`) it silently creates a bogus key (`-----semicolon----- debug`), and with characters such as `(`, `?`, `!`, `&` or `|` the parse fails. Keep notes out of the file or use plain words without these characters.

A syntax error is not reported nicely. `parse_ini_string()` returns `false`, and booting dies with a PHP `TypeError` from `overwriteAttributes()` (it expects an array). This happens while the configuration is loaded, before the exception handling is installed, so the framework's error page and logging do not apply: you get PHP's raw fatal error (or a blank page, depending on `display_errors`). Check the web server or PHP error log if the app suddenly fails to boot after editing the INI.

## Environment variable overrides
With `allow_env_config = true` in the INI, a setting `key` is overridden by the environment variable `CONFIG_KEY` (`CONFIG_` plus the upper-cased key, so `dbpassword` becomes `CONFIG_DBPASSWORD`; further examples are `CONFIG_DBHOST`, `CONFIG_MAIL_SMTP`, `CONFIG_ASSETVERSION` and `CONFIG_FORGOTPASSWORDTIMESPAN`).

If `allow_env_config` is absent, the variables are not read at all. The check is plain truthiness: any non-empty string other than `0` enables it, including the text `false` from a quoted INI value (`allow_env_config = "false"`), while an unquoted `false` is parsed to an empty string and disables it.

Only keys that already exist are overridden. The framework walks over the keys known from its built-in defaults and the INI, so a variable for any other key is silently ignored. This includes `allow_env_config` itself, which has to be set in the INI. Declare a placeholder in `z_settings.ini` for every setting you want to supply through the environment:

```ini
dbpassword = env
```

The value of the placeholder is arbitrary and replaced as soon as `CONFIG_DBPASSWORD` is set, the project stub uses `ENV`. Missing the placeholder is the usual reason why an environment variable seems to have no effect.

The value is taken as a raw string and skips the INI normalisation: in the INI file `false`, `off` and `no` are parsed to an empty string and `true` and `on` to `1`, but an environment variable keeps its text as is. `CONFIG_LOGGER_ENABLED=false` is therefore the string `false`, which PHP treats as truthy, and the logger stays enabled. To switch a flag off, set the variable to `0` or to an empty value (`CONFIG_LOGGER_ENABLED=0`), to switch it on use `1`.

## What do you actually put in here?
- Database connection details
- Connections to other external tools like a Mailer, Storage or Api keys.
- Basic Framework settings

## Example
```php
public function action_chatgpt(Request $req, Response $res) {
    $chatgptApiKey = $res->getBooterSettings("chatgpt_api_key");

    /* remaining code */

    return $res->render("admin/chatgpt");
}
```


## Purpose
This helps manage credentials and settings more easily, while keeping sensitive data separate from everything else.
