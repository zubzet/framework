<?php
    use ZubZet\Framework\Database\Migration\Seed;

    class TestSeeding extends Seed {

        public function run() {
            $this->insert("migration_seed", [
                "name" => "Seed Entry 2"
            ]);
        }

    }
