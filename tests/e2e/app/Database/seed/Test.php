<?php
    use ZubZet\Framework\Migration\Seed;

    class Test extends Seed {

        public function run() {
            $this->insert("model_test_insert", [
                "value" => "test value"
            ]);
        }

    }
