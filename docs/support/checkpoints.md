# Checkpoints

A checkpoint is a snapshot of selected properties of an object that can be put back later. It is for code that has to run something in between which overwrites the state of an object it still needs. The database connection does this around its slow query log: the log entry is an `INSERT` through the same connection, which would replace the result of the query that is being logged.

The following parts exist in `ZubZet\Framework\Support\Checkpoint`:

- `Checkpointable`: the interface a class has to implement.
- `CanCheckpoint`: the trait that implements it.
- `IncludeInCheckpoint`: the attribute that marks the properties to snapshot.
- `Checkpoint`: the handle with the one method `restore()`.

```php
use ZubZet\Framework\Support\Checkpoint\CanCheckpoint;
use ZubZet\Framework\Support\Checkpoint\Checkpointable;
use ZubZet\Framework\Support\Checkpoint\IncludeInCheckpoint;

class Importer implements Checkpointable {
    use CanCheckpoint;

    #[IncludeInCheckpoint]
    public array $rows = [];

    #[IncludeInCheckpoint]
    public int $position = 0;

    public string $source = "";

    public function preview(): array {
        $checkpoint = $this->checkpointCurrentState();

        try {
            return $this->readAhead();
        } finally {
            // $rows and $position are back to what they were, $source is not touched
            $checkpoint->restore();
        }
    }
}
```

**Syntax:** `checkpointCurrentState(?array $properties = null, ?string $attributeClass = IncludeInCheckpoint::class): Checkpoint`

* **$properties**: The names of the properties to snapshot. With `null` they are discovered by the attribute.
* **$attributeClass**: Only used while discovering: keeps the properties that carry this attribute. Pass an attribute of your own to keep several sets apart, or `null` to take every non-static property.

Things to know:

- **Only public properties work.** Marking or naming a private or protected one fails, and so does `attributeClass: null` on a class that has any.
- A property that was uninitialized at the snapshot is uninitialized again after `restore()`.
- Values are copied the way PHP assigns them: arrays and scalars are restored as they were, an object is the same instance afterwards, with whatever happened to it in between.
- Restore in a `finally` block, so the state is also put back when the code in between throws.
