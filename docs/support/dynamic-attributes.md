# Dynamic Attributes

The trait `ZubZet\Framework\Support\HasDynamicAttributes` gives a class a key and value store that reads like properties. The framework keeps its [configuration](../core-features/configuration.md) in it. Unlike plain dynamic properties, reading a key that was never set is an error instead of a silent `null`, so a typo in a key shows up at once.

!!! note "This can help you with compatibility"
    PHP 8.2 deprecated dynamic properties: setting a property a class does not declare raises a deprecation notice, and PHP 9 turns it into an error. Code that was written before relied on exactly that, the framework's own settings object included. The trait keeps such code working on every PHP version without the notice. For a new class, declare its properties instead and reach for the trait only when the keys are not known in advance.

```php
use ZubZet\Framework\Support\HasDynamicAttributes;

class Preferences {
    use HasDynamicAttributes;
}

$preferences = new Preferences();
$preferences->theme = "dark";

echo $preferences->theme;         // "dark"
isset($preferences->language);    // false
echo $preferences->language;      // throws an InvalidArgumentException
```

| Method | Behavior |
| --- | --- |
| `$object->key = $value` | Sets one attribute. |
| `$object->key` | Returns it, throws an `InvalidArgumentException` when it was never set. |
| `isset($object->key)` | Whether it was set. Also `true` for an attribute that holds `null`. |
| `getAllAttributes(): array` | Every attribute as an array. |
| `setAttributes(array $attributes): void` | Replaces all attributes. |
| `overwriteAttributes(array $attributes): void` | Merges into the existing ones, the given values win. |

Things to know:

- `$object->settings` is reserved for reading: it returns all attributes, like `getAllAttributes()`. An attribute named `settings` can be set, but not read back that way.
- The trait only takes over for names that are not declared. A property the class declares itself behaves like any other property.
