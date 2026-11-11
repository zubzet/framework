<?php

namespace ZubZet\Framework\Storage;

use ZubZet\Framework\Authentication\AuthenticationObject;
use ZubZet\Framework\Authentication\HandleTrait;
use ZubZet\Framework\Authentication\RetrievalTrait;

class File extends AuthenticationObject {

    use RetrievalTrait;
    use HandleTrait;

    public static string $dbTable = "z_file";
    public static array $dbExpression = [];

    public function __construct(array $data) {
        parent::__construct($data);
        $this->loadObject($data);
    }

    public function loadObject(array $data): void {
        $this->data = $data;
    }

    public static function add(string $reference, string $name = "", string $type = "", string $extension = "", int $size = 0): File {
        $id = model("z_file")->add($reference, $type, $name, $extension, $size);
        return static::byId($id);
    }

    public static function byReference(string $reference): ?File {
        return static::byIdentifier("reference", $reference);
    }

    public function reference(): string {
        return $this->getField("reference");
    }

    public function name(): string {
        return $this->getField("name");
    }

    public function type(): string {
        return $this->getField("type");
    }

    public function extension(): string {
        return $this->getField("extension");
    }

    public function size(): int {
        return $this->getField("size");
    }

    public function created(): string {
        return $this->getField("created");
    }

    public function replaceWith(File $file): void {
        model("z_file")->replaceWith($this, $file);
        $this->setField("replaced_by", $file->id());
    }

    public function replacedBy(): ?File {
        $replacedBy = $this->getField("replaced_by");
        if(is_null($replacedBy)) return null;

        return static::byId($replacedBy);
    }

    // Only deactivates the row, the stored file itself is left untouched
    public function remove(): void {
        model("z_file")->remove($this);
        $this->clearFields();
        $this->nullId();
    }
}
