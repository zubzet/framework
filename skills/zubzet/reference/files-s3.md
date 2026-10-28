# Files and S3

Framework docs: `vendor/zubzet/framework/docs/forms/file-uploads.md`.

## Principles

- User files never live in `webroot/`, never in `uploads/` on the web server, never as `public-read` by default. They are objects in S3 under a per purpose prefix, referenced from the database, and reached through signed URLs.
- Always prefer signing over public objects, because a signed link stops working when access is revoked. Public-read is acceptable only for a logo or similar branding that every anonymous page shows, and that choice is made explicitly, never by default.
- `S3Model` is the only class that talks to the S3 client. Controllers and views never build S3 URLs or paths.

## Configuration keys

In `z_settings.ini`, overridable through `CONFIG_S3_...` environment variables:

```
s3_schema = https
s3_endpoint = s3.example.com
s3_region = eu-central-1
s3_bucket = customer-portal
s3_access_key = ...
s3_secret_key = ...
s3_base_url = https://files.example.com
```

## S3Model

If the project has no `S3Model` yet, write one on top of `async-aws/s3`, constructed as shown in `framework-api.md` (settings through `config()`). The public surface:

| Method | Use |
| --- | --- |
| `uploadObjectToPath($data, $path, $acl = "private", $mime, $name = null)` | Server side put. Only for small files relayed through PHP or files PHP generated. Sets `ContentDisposition` when a name is given. Default ACL must be `private`. |
| `signUpload($path, $name, $sizeBytes, $mime, $acl = "private", $expires = 60)` | Presigned PUT for the browser. Locks content type, length and disposition. |
| `signGet($path, $filename = null, $expires = 60)` | Presigned GET, inline (images, PDFs shown in the browser). |
| `signDownload($path, $filename = null, $expires = 60)` | Presigned GET as attachment, with the original filename. |
| `getObjectBytes($key)` | Read an object inside PHP (image processing, parsing). |
| `deleteObject($path)` | Remove an object. Call it when a row is hard deleted or replaced. |
| `getFullUrl($path)` | Unsigned URL. Only for public-read branding objects. |

If the S3 provider serves signed URLs under a different host than the API endpoint (bucket as host), put the signing against `s3_base_url` into a small `App\Objects\S3Presign` class and let `S3Model` use it.

## Storage model: the `asset` table

New stacks use an `asset` table plus `AssetModel`. It may additionally carry a `fileId` to the framework's `z_file` row when a feature needs it.

```sql
CREATE TABLE `asset` (
    `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    `uploader` INT NOT NULL,

    `type` VARCHAR(100) NOT NULL COMMENT 'Directory in S3',
    `name` VARCHAR(255) NOT NULL,
    `extension` VARCHAR(20) NOT NULL,
    `mime` VARCHAR(100) NOT NULL,
    `size` INT NULL,
    `uploaded` TINYINT(1) NOT NULL DEFAULT 1,

    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),

    INDEX(`uuid`, `active`),
    INDEX(`uploader`)
);
```

Object key: `"$type/$uuid.$extension"`. The original name is stored in `name` and only reappears in the `Content-Disposition` of the signed link. Business tables reference `assetId`.

`AssetModel` methods: `addAsset(array $file, string $directory, int $userId): int` (PHP relay path), `add(...)` (row only, for signed uploads with `uploaded = 0`), `markUploaded(int $assetId, int $uploader)`, `validateOwner(int $assetId, int $uploader): ?int`, `getById(int $assetId)` returning the row plus a signed URL.

## Upload flows

**Small files (a CSV, a PDF, a logo) through PHP.** A Z.Forms `file` field, validated with `->file(FILE_SIZE_10MB, ["csv"])`, then:

```php
$file = $formResult->fields[0]->value;   // the $_FILES entry
$assetId = model("Asset")->addAsset($file, "exclusion-list", user()->userId);
```

`addAsset` reads the temp file, uploads it `private`, inserts the row, returns the id.

Do not persist a file field with `insertDatabase` / `updateDatabase`: those move the upload into the local `uploads/` folder and store a `z_file` id, which is the framework's disk based path (`docs/forms/file-uploads.md`). Validate with `->file(...)`, then hand `$formResult->fields[...]->value` (the `$_FILES` entry) to `AssetModel`, and persist the other fields with the `$fixed` array or a model method. Validate extension and size with the rule, not by hand. The local `uploads/` folder is a framework feature, not a stack feature: every stack is stateless and runs as several replicas in replaceable containers without volumes, so a file on the container's disk is gone with the next deploy and was never visible to the other replicas. Every project uses S3, without exception; a single deviation is already a finding. The same holds for every other local file (a cache, an export, a lock, a generated PDF): it exists at most for the request that creates it, and whatever must outlive the request goes to S3 or the database. If a task seems to need one, ask.

**Large or many files (images, videos, bulk uploads) through a signed PUT.** Three steps, each its own `isAction`:

1. `register-upload`: validate name, size, mime; insert the `asset` row with `uploaded = 0`; return `signUpload(...)` and the asset id.
2. Browser PUTs the bytes straight to S3 with the signed URL (`fetch(url, {method: "PUT", body: file, headers: {"Content-Type": mime}})`, the one place `fetch` is legitimate).
3. `finish-upload`: `validateOwner`, `markUploaded`; return the signed download link.

Reads skip rows with `uploaded = 0`.

**Files PHP creates itself** (rendered images, exports, generated PDFs) go through `uploadObjectToPath` and an `asset` row like any other file.

## Delivering files

- The model that returns the business row attaches the signed URL (`$order["attachment"] = $this->getModel("S3")->signDownload($path, $name)`), or the controller does it before rendering. Both are fine. Views never sign.
- Default expiry is 60 minutes. Longer expiry (hours) is a performance exception for hot, non sensitive files like logos and module icons.
- Lists: resolve and sign in one pass, not per row with a query each. Keep a request level cache keyed by uuid inside the model when the same file appears many times.
- Download buttons are plain `<a href="{{ $asset["url"] }}" target="_blank">` with the signed URL. The page is already authenticated; the link expiring later is intended.
- Deleting a business row soft deletes it. Deleting the asset row soft deletes it too; `deleteObject` only when the object is replaced or really removed.
