<?php

namespace ZubZet\Framework\Authentication\Permission;

trait HandleTrait {

    /**
     * Refresh the current permission object (user or role) by clearing all its fields and setting them new
     * 
     * @throws \RuntimeException If the object no longer exists
     * @return void
     */
    public function refresh(): void {
        self::clearFields();

        $object = self::byId($this->id());
        if(is_null($object)) throw new \RuntimeException("Object no longer exists");

        // Reload object data
        self::loadObject($object->data);
    }

}
