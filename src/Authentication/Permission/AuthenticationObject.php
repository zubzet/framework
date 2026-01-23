<?php

namespace ZubZet\Framework\Authentication\Permission;

class AuthenticationObject {

    /** @var int The ID of the authentication object */
    private ?int $id = null;


    /** @var array|null The data of the authentication object */
    protected ?array $data = null;

    // Setting the id
    public function __construct(array $data) {
        $this->id = $data['id'];
    }

    // Check if the instance exists
    function checkInstance() {
        if(is_null($this->id)) throw new \InvalidArgumentException("Instance no longer exists");
    }

    // Get a field value
    // Throws exception if field does not exist
    public function getField(string $field) {
        $this->checkInstance();

        if(!array_key_exists($field, $this->data)) {
            throw new \InvalidArgumentException("Field '{$field}' does not exist");
        }

        return $this->data[$field];
    }

    // Clear all fields of the object by setting data to an empty array
    public function clearFields(): void {
        $this->checkInstance();

        $this->data = [];
    }

    // Get all data of the object
    public function getAll(): array {
        $this->checkInstance();

        return $this->data ?? [];
    }

    // Set a field value
    protected function setField(string $field, mixed $value): void {
        $this->checkInstance();

        if(is_null($this->data)) $this->data = [];

        $this->data[$field] = $value;
    }

    public function id() {
        $this->checkInstance();

        return $this->id;
    }

    public function nullId() {
        $this->checkInstance();

        $this->id = null;
        $this->data = null;
    }
}
