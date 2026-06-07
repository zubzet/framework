# Password Handling

ZubZet hashes passwords with PHP's native **Argon2id**. The framework stores
enough information alongside every hash to verify it, to recognise older hashes,
and to upgrade them to the current algorithm automatically. Application code
normally never touches a hash directly: the [`User`](permission-system.md) API
covers creating users, setting passwords, and checking a login.

## Where passwords are stored

Credentials live on the `z_user` table:

| Column | Purpose |
| ------ | ------- |
| `password` | The stored credential (an Argon2id hash for current accounts). |
| `password_scheme` | Which format `password` is in: `native`, `legacy`, or `onion`. `NULL` for accounts without a password. |
| `salt` | Per row salt for `legacy` and `onion` rows. `NULL` for `native`, because Argon2id embeds its own salt. |
| `last_password_rehash_at` | When the stored hash was last produced. Useful for credential age policies. |

There is no separate salt for native hashes. A native `password` value already
contains the algorithm, the cost parameters, and a random salt in one
self describing string, which is what makes transparent upgrades possible.

## The three schemes

The `password_scheme` column tells the verifier how to read a stored value, so
the three formats can coexist while older accounts upgrade on their own.

| Scheme | `password` holds | `salt` | Notes |
| ------ | ---------------- | ------ | ----- |
| `native` | An Argon2id hash from `password_hash()`. | `NULL` | The target format for every account. |
| `legacy` | The hash produced by the framework's previous scheme. | set | Verify only. Upgraded to `native` on the next login. |
| `onion` | A native Argon2id hash wrapping the legacy value. | set | A dormant account moved onto Argon2id ahead of its next login. Upgraded to `native` when the owner logs in. |

## Adding users and setting passwords

For building features and APIs, work through the `User` object. The plaintext is
hashed for you, and the resulting account is always `native`.

### Create a user with a password

```php
use ZubZet\Framework\Authentication\Permission\User;

$user = User::add("person@example.com", "the-plaintext-password");
```

`User::add()` also accepts `null` for the password to create an account whose
credential is set later (for example invite or single sign on flows):

```php
$user = User::add("person@example.com", null);
// ... later ...
$user->updatePassword("the-plaintext-password");
```

### Set or change a password

```php
$user->updatePassword("the-new-plaintext-password");
```

This writes a fresh `native` Argon2id hash, clears the salt, stamps
`last_password_rehash_at`, and invalidates the user's existing sessions.

### Check a password during login

```php
$user = User::byEmail($email);

if ($user && $user->verifyPassword($plaintext)) {
    // success
}
```

`User::verifyPassword()` returns a boolean and is self healing: on a correct
login it transparently upgrades a stale stored hash to a current `native` hash
(see [Self healing upgrades](#self-healing-upgrades)). It is the password check
used by the framework's own login flow.

## The hash and verify API

`User` is built on the lower level `ZubZet\Framework\Authentication\PasswordHash\Password`
class, which you can use directly when you are not working with a `User` object.

### Hashing

```php
use ZubZet\Framework\Authentication\PasswordHash\Password;

$stored = Password::hash($plaintext); // Argon2id
```

`Password::hash()` rejects an empty or oversized input
(`MIN_LENGTH_BYTES` to `MAX_LENGTH_BYTES`, the upper bound being a denial of
service guard) by throwing an `InvalidArgumentException`.

### Verifying

```php
$result = Password::verify($plaintext, $stored);

if ($result->isCorrect()) {
    // password matched
}
```

`Password::verify()` returns a `Verification` value object rather than a bare
boolean, so a single call can also report whether the stored hash should be
refreshed:

| Method | Returns |
| ------ | ------- |
| `isCorrect()` | Whether the password matched. |
| `isUpgradeNeeded()` | Whether the stored hash is stale and should be replaced. |
| `upgradePassword()` | The fresh `native` hash to persist. Guard with `isUpgradeNeeded()` first; calling it when no upgrade is pending throws a `LogicException`. |

The hash inside `upgradePassword()` is computed only when you call it, so a
plain `isCorrect()` check never pays for an upgrade it does not use.

For `legacy` and `onion` values, pass the scheme and salt so the verifier knows
how to read them:

```php
$result = Password::verify($plaintext, $stored, Password::LEGACY, $salt);
```

## Self healing upgrades

ZubZet keeps stored hashes current without ever asking users to reset a working
password. On a successful login, the stored hash is upgraded to a fresh
`native` Argon2id hash when any of the following is true:

- the account is still `legacy` or `onion`, or
- the account is `native` but was hashed below the current cost.

The second case relies on PHP's `password_needs_rehash()`. When you raise the
Argon2id cost (see below), existing users are migrated one at a time as they log
in, with no bulk operation and no forced reset.

### Future proofing the work factor

The work factor is PHP's Argon2id default cost. Hardware gets faster over time,
so the recommended practice is to re measure every year or two and raise the
cost when appropriate. Because the cost is embedded in each stored hash, raising
it does not invalidate existing passwords: `password_needs_rehash()` simply
reports the older hashes as stale, and the self healing path upgrades them on
the next login.

## Migrating an existing installation

When you upgrade the framework, the schema migration runs automatically and
marks every existing password row as `legacy`. Those accounts keep working
unchanged and upgrade themselves to `native` on the next login.

Rehash on login only reaches accounts that actually log in. To bring accounts
that have not logged in yet onto Argon2id as well, run the onion migration once:

```bash
php index.php auth:migrate-hashing
```

This wraps every `legacy` hash in an Argon2id layer and marks the row `onion`,
so the whole table moves onto the modern algorithm rather than only the active
accounts. It is idempotent, so it is safe to run more than once, and the first
successful login peels an `onion` row back to a single `native` hash.

Wrapping an existing hash inside a stronger one is the same layered "onion"
technique Facebook has publicly described for modernising its own password
storage, which is where the name comes from.

## A short history

For years ZubZet hashed passwords with SHA-512 plus a per user salt and an
additional transformation step, provided by a dedicated hashing library. That
scheme served the framework reliably and shipped in production for a long time.

Version 1.2.0 modernises password storage onto PHP's native Argon2id, the
algorithm recommended by the current OWASP Password Storage guidance. Argon2id
is memory hard and purpose built for password storage, the native PHP functions
are maintained as part of the language and run in constant time, and every
stored value embeds its own algorithm, cost, and salt. That self describing
format is what lets ZubZet recognise existing hashes and upgrade them
transparently, and it makes raising the work factor in the future a one line
change.

Existing passwords are safe and keep working. They are verified through the
`legacy` path and re hashed onto Argon2id automatically the next time their
owner logs in, so adopting the modern format needs no password reset.
