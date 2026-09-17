<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2026_09_17_session_token_index extends Migration {

        public function execute(): void {
            $table = $this->tableAlter("z_logintoken");
            $table->addIndex(["token"], "token");
        }
    }
?>
