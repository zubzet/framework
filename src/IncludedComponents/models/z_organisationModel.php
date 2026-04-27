<?php

use ZubZet\Framework\Authentication\Organisation;

    class z_organisationModel extends z_model  {

        public function create(?string $name): ?array {
            $sql = "INSERT INTO z_organisation (name) VALUES (?)";
            $insertedId = $this->exec($sql, "s", $name)->insertId;
            return $this->byId($insertedId);
        }

        public function update(Organisation $organisation, string $name): void {
            $sql = "UPDATE z_organisation SET
                    name = ?
                    WHERE id = ?";
            $this->exec($sql, "si", $name, $organisation->id());
        }

        public function byName(string $name): ?array {
            $sql = "SELECT * FROM z_organisation WHERE name = ?";
            return $this->exec($sql, "s", $name)->resultToLine();
        }

        public function byId(int $id): ?array {
            $sql = "SELECT * FROM z_organisation WHERE id = ?";
            return $this->exec($sql, "i", $id)->resultToLine();
        }

        public function remove(Organisation $organisation): void {
            $sql = "UPDATE z_organisation SET active = 0 WHERE id = ?";
            $this->exec($sql, "i", $organisation->id());
        }

    }

?>