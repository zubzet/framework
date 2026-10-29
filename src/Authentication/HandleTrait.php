<?php

namespace ZubZet\Framework\Authentication;

trait HandleTrait {

    /**
     * Refresh the current permission object (user, role, session, organization or API key) by clearing all its fields and setting them new
     * 
     * @throws \InvalidArgumentException If this instance was already detached
     * @throws \RuntimeException If the object no longer exists (its fields are already cleared by then)
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
