# Encryption

Some secrets have to be stored and read back later, for example the credentials of a third-party
service a customer enters in your application. Unlike a [password](password-handling.md), such a
value cannot be hashed, because the application needs the original. ZubZet encrypts these values
with a key from `z_config/z_settings.ini`, so a leaked database alone does not reveal them.

## Setting the key

Add an `encryption_key` of at least 32 characters to `z_config/z_settings.ini`:

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
    [Installation](../setup/installation.md)) instead of committing it. Values encrypted with a
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

$encrypted = Encryption::encrypt($secretKey);
$secretKey = Encryption::decrypt($encrypted);
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

A missing `encryption_key`, or one shorter than 32 characters, throws a `RuntimeException` on both
encryption and decryption, so a misconfiguration is noticed before anything is stored.

## Storage format

An encrypted value is plain text of the form `zenc1:` followed by base64, so it fits into a
`VARCHAR` or `TEXT` column. It is about `(length × 4 / 3) + 44` characters long; a `TEXT` column
avoids having to size it. The `zenc1:` prefix versions the format, which lets a later release
change the algorithm while still reading values stored today.

The cipher key is derived from `encryption_key` with HKDF-SHA256, so the setting may be any
sufficiently long random string.
