# Runtime Cache

`ZubZet\Framework\Support\RuntimeCache\RuntimeCache` remembers values for as long as the PHP script runs. It is memoization: for everything that is expensive to work out and does not change while the script runs.

!!! note "This is not a persistent cache"
    Nothing is written anywhere. Every request starts empty, no other request or server sees a value, and nothing expires or can be invalidated. A value that has to outlive the request, or be shared between requests, does not belong here.

How long "while the script runs" is depends on what runs:

| Script | A value lives |
| --- | --- |
| A web request | until the response is sent. |
| A console command | until the command ends. |
| A long-running worker | until the worker stops. |

Values are addressed by a type, which groups them, and a key within it.

```php
use ZubZet\Framework\Support\RuntimeCache\RuntimeCache;

function exchangeRates(): array {
    $cached = RuntimeCache::getOrNull("shop", "exchange_rates");
    if(!is_null($cached)) return $cached;

    // set() returns the value, so it can be stored and returned at once
    return RuntimeCache::set("shop", "exchange_rates", loadExchangeRates());
}
```

| Method | Behavior |
| --- | --- |
| `set(string $type, string $key, mixed $value): mixed` | Stores the value and returns it. |
| `get(string $type, string $key): mixed` | Returns the value, throws an `InvalidArgumentException` when it is not set. |
| `getOrNull(string $type, string $key): mixed` | Returns the value, or `null` when it is not set. |
| `has(string $type, string $key): bool` | Whether a value is set. |

Things to know:

- A stored `null` counts as not set: `has()` is `false` and `get()` throws.
- There is no way to remove a value, only to overwrite it. In a worker, a remembered value is therefore still there for the next job.
- Every type is yours to use. The framework keeps its own values (model instances, the loaded translator, the file lookups of the registry) in a store of its own, so no type or key of an application can collide with them.

## A store of its own

A class that extends `RuntimeCache` gets a separate store: its values are out of reach for `RuntimeCache` and for every other class extending it, whatever types and keys they use. A module uses this to stay clear of the application it is installed in.

```php
namespace Acme\Shop;

use ZubZet\Framework\Support\RuntimeCache\RuntimeCache;

final class ShopRuntimeCache extends RuntimeCache {}

ShopRuntimeCache::set("rates", "EUR", $rates);
```

The framework does the same with the internal `ZubZet\Framework\Support\RuntimeCache\InternalRuntimeCache`.
