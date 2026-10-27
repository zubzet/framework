<?php

namespace ZubZet\Framework\Authentication;

trait RetrievalTrait {

    /**
     * Get a list of all active objects of the calling class (user, role, session, organization, API key)
     *
     * @return static[] The list of active objects
     */
    public static function all(): array {
        $result = model("z_permission")->getAll(self::$dbTable, static::$dbExpression);

        if(empty($result)) return [];

        $objects = [];
        foreach($result as $data) {
            $objects[] = new static($data);
        }

        return $objects;
    }

    /**
     * Get a permission object (user or role) by one of its identifiers
     *
     * @param string $column The identifying column, e.g. "id" or "uuid"
     * @param int|string $identifier The value of the identifying column
     * @return object|null the permission object if found, null otherwise
     */
    public static function byIdentifier(string $column, int|string $identifier): ?static {
        $result = model("z_permission")->getByIdentifier(self::$dbTable, $column, $identifier, static::$dbExpression);

        if(is_null($result)) return null;

        return new static($result);
    }

    /**
     * Get a list of permission objects (user or role) by one of their identifiers
     *
     * @param string $column The identifying column, e.g. "id" or "uuid"
     * @param int[]|string[] $identifiers The values of the identifying column
     * @return object[] The list of permission objects
     */
    public static function byIdentifiers(string $column, array $identifiers): array {
        $results = model("z_permission")->getByIdentifiers(self::$dbTable, $column, $identifiers, static::$dbExpression);

        $objects = [];
        foreach($results as $data) {
            $objects[] = new static($data);
        }

        return $objects;
    }

    /**
     * Get an object (user, role, session, organization or API key) by its id
     *
     * @param int|string $id the id of the object
     * @return static|null the object if found, null if the id is unknown or inactive
     */
    public static function byId(int|string $id): ?static {
        return static::byIdentifier("id", $id);
    }

    /**
     * Get a list of objects (user, role, session, organization or API key) by their ids
     * Unknown or inactive ids are skipped, and calling it without ids throws a database exception
     *
     * @param int ...$ids The ids of the objects to retrieve
     * @return static[] the objects that were found
     */
    public static function byIds(int ...$ids): array {
        return static::byIdentifiers("id", $ids);
    }

    /**
     * Get a permission object (user or role) by its uuid
     *
     * @param string $uuid the uuid of the object
     * @return object|null the permission object if found, null otherwise
     */
    public static function byUuid(string $uuid): ?static {
        return static::byIdentifier("uuid", $uuid);
    }

    /**
     * Get a list of permission objects (user or role) by their uuids
     *
     * @param string ...$uuids The uuids of the objects to retrieve
     * @return object[] The list of permission objects
     */
    public static function byUuids(string ...$uuids): array {
        return static::byIdentifiers("uuid", $uuids);
    }
}
