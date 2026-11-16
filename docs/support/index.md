# Support

`ZubZet\Framework\Support` holds the small general-purpose tools the framework uses itself. They are not tied to a feature, so an application can use them as well.

| Tool | What it is for |
| --- | --- |
| [Filesystem](filesystem.md) | `mkdir()`, `copy()`, `mirror()` and `remove()`: the file operations PHP has no single call for. |
| [Runtime Cache](runtime-cache.md) | Remembering a computed value while the script runs. Memoization, not a persistent cache. |
| [Checkpoints](checkpoints.md) | Snapshotting properties of an object and restoring them later. |
| [Dynamic Attributes](dynamic-attributes.md) | A trait that gives a class a strict key and value store behind `$object->key`. |

The rest of the namespace is documented where it is used:

- The global functions (`config()`, `request()`, `e()`, `makeSlug()` and the others) of `GlobalReferences` and `Helpers`: [Global Helper Functions](../core-features/global-helper-functions.md).
- `Rest`, the JSON payload behind `$res->generateRest()`: [REST API](../core-features/rest-api.md).
- The commands `info:startup` and `install`: [Console Commands](../core-features/console-commands.md).
