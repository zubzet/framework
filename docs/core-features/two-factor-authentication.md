# Two Factor Authentication

An account can protect its logins with a second factor: a time based one time password (TOTP) from an authenticator app. The framework handles the enrollment, asks for the code during the login and can guard single actions behind a recent check. The codes are generated and verified with [`spomky-labs/otphp`](https://github.com/Spomky-Labs/otphp).

Users turn it on and off themselves on their [profile page](../z-admin/profile.md). The methods behind it are documented on the [User](access-control.md#two-factor) and [Session](access-control.md#two-factor-checks) objects.

---

## Enrollment

Turning two factor on takes two steps, so a secret that never reached the authenticator cannot lock anyone out:

1. `$user->startTwoFactor()` stores a fresh secret and returns it together with a provisioning URI, the two shapes an authenticator takes it in. The profile page shows the URI as a QR code. The account does not count as protected yet.
2. `$user->confirmTwoFactor($code)` turns two factor on once a code from the authenticator matches the stored secret.

Turning it off again takes a current code as well. An admin with `admin.user.edit` can turn it off for an account on the user page of the [Z-Admin panel](../z-admin/usage.md), which is the way back in for an owner who lost their authenticator.

A code is accepted one 30 second step either side of the current one, so a clock that drifts a little still works.

---

## Login

When the password of an account with two factor is correct, the login creates no session yet. It answers with a challenge instead:

```json
{"result": "success", "twoFactor": true, "challenge": "zub-..."}
```

The challenge is redeemed at `POST _zubzet/two-factor/login` with the fields `challenge` and `code`. A correct code creates the session, already stamped with a passed check. A challenge is valid for 10 minutes and can be redeemed once. Wrong codes count against the same limit as wrong passwords (`maxLoginTriesPerTimespan` within `maxLoginTriesTimespan`).

`Z.Presets.Login` does all of this on its own: it opens the two factor modal, sends the code and continues like a normal login once it is accepted. The modal markup ships with `<x-zubzet::body/>`, so every layout that uses the login has to render it (see [Layouts](layouts.md)).

---

## Guarding an Action

A login can live for days. For a sensitive action, `$req->requireFreshTwoFactor()` demands that the session passed a check recently:

```php
public function action_delete_account(Request $req, Response $res) {
    $req->requireFreshTwoFactor();

    // Only reached with a recent check, or for an account without two factor
}
```

The check counts as recent within `two_factor_freshness_seconds` (defaults to 900). A different window can be passed as the first argument. Accounts without two factor always pass, there is nothing to renew. An [api key](access-control.md#api-keys) is never stamped, so for an account with two factor it never passes.

Without further arguments a stale session is answered with a JSON error carrying `twoFactorRenew` and the request ends. To answer the client yourself, pass `boolResult: true`:

```php
if(!$req->requireFreshTwoFactor(boolResult: true)) {
    return $res->error("Two factor required", ["twoFactorRenew" => true]);
}
```

`Z.Request.action()`, `Z.Request.root()` and `Z.Forms` react to `twoFactorRenew` on their own: they open the modal, send the code to `POST _zubzet/two-factor/refresh`, which stamps the current session, and tell the user to try again. `Z.Presets.RefreshTwoFactor(onDone)` opens the same modal on demand.

Every session may send 5 wrong codes on a renewal or when turning two factor off. After the last one it is signed out. A passed check gives the session all its tries back.

---

## Settings

| Setting | Default | Meaning |
| ------- | ------- | ------- |
| `two_factor_freshness_seconds` | `900` | How long a passed check counts as recent for `requireFreshTwoFactor()` |

The texts of the modal come from `Z.Lang` in `Z.js` and can be overwritten in the layout after the layout essentials are embedded: `error_two_factor_incomplete`, `two_factor_renewed` and `error_password_wrong` for the password change.
