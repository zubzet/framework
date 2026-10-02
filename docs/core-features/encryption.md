# Encryption

Some secrets have to be stored and read back later, for example the credentials of a third-party
service a customer enters in your application. Unlike a [password](password-handling.md), such a
value cannot be hashed, because the application needs the original. ZubZet encrypts these values
with a key from `z_config/z_settings.ini`, so a leaked database alone does not reveal them.

## Setting the key

Encryption needs PHP's OpenSSL extension, which the framework declares as a requirement in its
`composer.json`.

Add an `encryption_key` of at least 32 bytes to `z_config/z_settings.ini`. The length is counted
in bytes, so a key of plain ASCII characters, like the generated one below, needs 32 characters:

```ini
encryption_key = 2f6c1d9e8a7b4c3d5e6f708192a3b4c5d6e7f8091a2b3c4d5e6f708192a3b4c5
```

Generate a random one, for example with:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

!!! warning "Keep the key out of the repository and never lose it"
    Anyone with the key and the database can decrypt every value, so provide it through the
    environment (`CONFIG_ENCRYPTION_KEY` with `allow_env_config = true`, see
    [Installation](../setup/installation.md)) instead of committing it. Environment variables only
    override settings that exist in `z_settings.ini`, so keep an empty `encryption_key =` line
    there; without it `CONFIG_ENCRYPTION_KEY` is ignored. Values encrypted with a
    key cannot be decrypted without it: changing or losing the key makes every stored value
    unreadable.

## Encrypting and decrypting

The global helpers `encryptSecret()` and `decryptSecret()` cover the usual case:

```php
// Before saving
$model->updateCredentials($websiteId, $accessKey, encryptSecret($secretKey));

// Before using
$secretKey = decryptSecret($website["s3_secret_key"]);
```

The same methods are available on the `ZubZet\Framework\Security\Encryption` class:

```php
use ZubZet\Framework\Security\Encryption;

$encrypted = Encryption::encryptSecret($secretKey);
$secretKey = Encryption::decryptSecret($encrypted);
```

Every call to `encryptSecret()` uses a fresh random IV, so encrypting the same value twice gives two
different results. Compare decrypted values, never encrypted ones.

## Detecting a wrong key

Values are encrypted with AES-256-GCM, which authenticates the ciphertext. Decryption therefore
never returns garbage: when the key is wrong, or the stored value was modified, truncated or is not
an encrypted value at all, `decryptSecret()` throws a
`ZubZet\Framework\Security\DecryptionException`.

```php
use ZubZet\Framework\Security\DecryptionException;

try {
    $secretKey = decryptSecret($website["s3_secret_key"]);
} catch(DecryptionException $e) {
    // Wrong encryption_key, or the stored value is damaged
}
```

A missing `encryption_key`, or one shorter than 32 bytes, throws a `RuntimeException`, so a
misconfiguration is noticed before anything is stored. Decryption checks the value first: a value
that is not in the encrypted format throws the `DecryptionException` even when the key is missing.

## Storage format

An encrypted value is plain text of the form `zenc:aes-256-gcm:` followed by base64url. It only
contains `A-Z`, `a-z`, `0-9`, `-`, `_` and `:`, so it can be stored in a `VARCHAR` or `TEXT` column
and passed through URLs, JSON or environment variables without escaping. It is about
`(length × 4 / 3) + 55` characters long; a `TEXT` column avoids having to size it.

Any string can be encrypted, including binary data. To store several values together, encrypt them
as JSON:

```php
$stored = encryptSecret(json_encode(["accessKey" => $accessKey, "secretKey" => $secretKey]));
$credentials = json_decode(decryptSecret($stored), true);
```

The cipher key is derived from `encryption_key` with HKDF-SHA256, so the setting may be any
sufficiently long random string.

## Changing the algorithm

Every value names the cipher it was encrypted with, so the framework can switch to a different
algorithm without breaking stored values. A cipher is a class implementing
`ZubZet\Framework\Security\Cipher\Cipher`, registered under a fixed identifier in
`Encryption::CIPHERS`. A new default only affects values encrypted from then on; existing values
keep decrypting with the cipher they name, as long as it stays registered. Only authenticated
ciphers qualify, otherwise a wrong key could no longer be detected.
