# Filesystem

`ZubZet\Framework\Support\Filesystem` covers the file operations PHP has no single call for. Every method throws a `RuntimeException` that names the path when it fails, so there is no return value to check.

```php
use ZubZet\Framework\Support\Filesystem;

Filesystem::mkdir("uploads/invoices/2026");
Filesystem::copy("uploads/logo.png", "backup/logo.png");
Filesystem::mirror("uploads/invoices", "backup/invoices");
Filesystem::remove(["uploads/tmp", "uploads/cache"]);
```

## Compatibility with symfony/filesystem

The four methods have the signatures and the behavior of the same methods in [`symfony/filesystem`](https://symfony.com/doc/current/components/filesystem.html), which the framework required before. Code written against that class keeps working after swapping the import, also where it calls the methods on an instance:

```php
use ZubZet\Framework\Support\Filesystem; // was Symfony\Component\Filesystem\Filesystem

$filesystem = new Filesystem();
$filesystem->remove($directory);
```

What is different:

- **Only these four methods exist.** Symfony's `dumpFile()`, `exists()`, `rename()`, `symlink()`, `chmod()`, `touch()`, `appendToFile()` and the `Path` class have no counterpart. An application that needs them requires `symfony/filesystem` itself.
- **Exceptions are plain `RuntimeException`s.** Symfony's `IOException` is one as well, so `catch(\RuntimeException $e)` covers both. A `catch` that names Symfony's `IOExceptionInterface` or `FileNotFoundException` has to be changed.
- **Local paths only.** `copy()` does not fetch its origin from a URL.

## `mkdir()`

Creates directories with every missing parent.

**Syntax:** `Filesystem::mkdir(string|iterable $dirs, int $mode = 0777): void`

A directory that already exists is left alone, also when another process creates it at the same moment. PHP's `mkdir()` fails in both cases. The mode is lowered by the umask, so the default usually results in `0755`.

## `copy()`

Copies one file with its permissions and its modification time.

**Syntax:** `Filesystem::copy(string $originFile, string $targetFile, bool $overwriteNewerFiles = false): void`

Missing parent directories of the target are created. An existing target is **only replaced when the origin is newer**; pass `true` as the third argument to replace it in any case. An origin that is not a file throws, a directory is copied with `mirror()`.

## `mirror()`

Copies a directory with everything in it.

**Syntax:** `Filesystem::mirror(string $originDir, string $targetDir, ?\Traversable $iterator = null, array $options = []): void`

Files are copied like with `copy()`, so newer files in the target stay, and so does whatever only exists in the target. A link is copied as a link.

* **$iterator**: The entries to copy, as `SplFileInfo`. By default everything in the origin.
* **$options**:
    * `override`: also replace files in the target that are newer than their origin.
    * `delete`: remove what exists in the target but not in the origin.
    * `copy_on_windows`: copy what a link points to instead of the link.

## `remove()`

Removes files, links and directories with everything in them.

**Syntax:** `Filesystem::remove(string|iterable $files): void`

- A path that does not exist is left alone, so there is no need to check first.
- A link is removed itself. Its target is never touched, and a linked directory is not descended into.

!!! warning
    `remove()` deletes recursively and does not ask. Never build its path from a value that can be empty: `Filesystem::remove($base . $name)` with an empty `$name` removes all of `$base`.
