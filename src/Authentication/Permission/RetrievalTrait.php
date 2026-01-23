<?php

namespace ZubZet\Framework\Authentication\Permission;

trait RetrievalTrait {

    /**
     * Get a list of permission objects (user or role)
     *
     * @return object[]
     */
    public static function all(): array {
        $result = model("z_permission")->getAll(self::$dbTable);

        if(empty($result)) return [];

        $objects = [];
        foreach($result as $data) {
            $objects[] = new static($data);
        }

        return $objects;
    }

    /**
     * Get a permission object (user or role) by its id
     *
     * @param int|string $id the id of the object
     * @return object|null the permission object if found, null otherwise
     */
    public static function byId(int|string $id): ?static {
        $result = model("z_permission")->getById($id, self::$dbTable);

        if(is_null($result)) return null;

        return new static($result);
    }

    /**
     * Get a list of permission objects (user or role) by their ids
     *
     * @param int ...$ids The ids of the objects to retrieve
     * @return object[] The list of permission objects
     */
    public static function byIds(int ...$ids): array {
        $results = model("z_permission")->getByIds(self::$dbTable, ...$ids);

        $objects = [];
        foreach($results as $data) {
            $objects[] = new static($data);
        }

        return $objects;
    }
}
