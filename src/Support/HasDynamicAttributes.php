<?php

    namespace ZubZet\Framework\Support;

    trait HasDynamicAttributes {

        private array $dynamicAttributesStore = [];

        public function __set(string $name, mixed $value): void {
            $this->dynamicAttributesStore[$name] = $value;
        }

        public function __get(string $name): mixed {
            if($name === "dynamicAttributesStore") {
                throw new \InvalidArgumentException("The attribute store cannot be accessed directly.");
            }

            if($name === "settings") {
                return $this->dynamicAttributesStore;
            }

            if(!array_key_exists($name, $this->dynamicAttributesStore)) {
                throw new \InvalidArgumentException("The attribute '$name' does not exist in the attribute store (yet).");
            }
            return $this->dynamicAttributesStore[$name];
        }

        public function __isset(string $name): bool {
            if($name === "dynamicAttributesStore") {
                throw new \InvalidArgumentException("The attribute store cannot be accessed directly.");
            }

            return array_key_exists($name, $this->dynamicAttributesStore);
        }

        public function getAllAttributes(): array {
            return $this->dynamicAttributesStore;
        }

        public function setAttributes(array $attributes): void {
            $this->dynamicAttributesStore = $attributes;
        }

        public function overwriteAttributes(array $attributes): void {
            $this->dynamicAttributesStore = array_merge(
                $this->dynamicAttributesStore,
                $attributes,
            );
        }

    }
?>