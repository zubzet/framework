# Secrets

Some secrets have to be stored by the application and read back later. A typical case: your
application lets each customer connect their own S3 bucket, so the customer enters an access key
and a secret key that you have to save in the database and use on every upload. Unlike a
[password](password-handling.md), such a value cannot be hashed, because the application needs
the original to talk to S3.

ZubZet encrypts these values at rest with a key from `z_config/z_settings.ini`. The database only
ever holds the encrypted value, so a leaked dump or backup alone does not reveal the customers'
credentials. The application decrypts a value right before using it.

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

## Storing a secret

The global helpers `encryptSecret()` and `decryptSecret()` cover the usual case. Encrypt the value
before it reaches the model, and decrypt it only where it is used:

```php
// Saving the bucket credentials a customer entered
model("Bucket")->saveCredentials(
    $companyId,
    $req->getPost("accessKey"),
    encryptSecret($req->getPost("secretKey")),
);

// Using them for an upload
$bucket = model("Bucket")->getByCompany($companyId);
$secretKey = decryptSecret($bucket["secret_key"]);
```

The same methods are available on the `ZubZet\Framework\Security\Secrets\Encryption` class:

```php
use ZubZet\Framework\Security\Secrets\Encryption;

$encrypted = Encryption::encryptSecret($secretKey);
$secretKey = Encryption::decryptSecret($encrypted);
```

Every call to `encryptSecret()` uses a fresh random IV, so encrypting the same value twice gives two
different results. Compare decrypted values, never encrypted ones, and do not search or index the
encrypted column.

## Detecting a wrong key

Values are encrypted with AES-256-GCM, which authenticates the ciphertext. Decryption therefore
never returns garbage: when the key is wrong, or the stored value was modified, truncated or is not
an encrypted value at all, `decryptSecret()` throws a
`ZubZet\Framework\Security\Secrets\DecryptionException`.

```php
use ZubZet\Framework\Security\Secrets\DecryptionException;

try {
    $secretKey = decryptSecret($bucket["secret_key"]);
} catch(DecryptionException $e) {
    // Wrong encryption_key, or the stored value is damaged
}
```

A missing `encryption_key`, or one shorter than 32 bytes, throws a `RuntimeException`, so a
misconfiguration is noticed before anything is stored. Decryption checks the value first: a value
that is not in the encrypted format throws the `DecryptionException` even when the key is missing.

## Storage format

An encrypted value is plain text of the form `zenc:aes-256-gcm:` followed by base64url. Encrypting
the 40 character S3 secret key `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY` stores this in the
database:

```text
zenc:aes-256-gcm:ydhF8rgskJ4UnMKoACkDLA2jApTRBmneKl8pqgVYTBb2QOiipP6hs7KLeNTy1JEWkeW2FWxaulfmY_H4PNn46oyTAh4
```

`zenc` marks the value as encrypted, `aes-256-gcm` names the cipher, and the rest holds the random
IV, the authentication tag and the ciphertext. The value only contains `A-Z`, `a-z`, `0-9`, `-`,
`_` and `:`, so it can be stored in a `VARCHAR` or `TEXT` column and passed through URLs, JSON or
environment variables without escaping. It is about `(length × 4 / 3) + 55` characters long, 108
for the key above; a `TEXT` column avoids having to size it.

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
`ZubZet\Framework\Security\Secrets\Cipher\Cipher`, registered under a fixed identifier in
`Encryption::CIPHERS`. A new default only affects values encrypted from then on; existing values
keep decrypting with the cipher they name, as long as it stays registered. Only authenticated
ciphers qualify, otherwise a wrong key could no longer be detected.
