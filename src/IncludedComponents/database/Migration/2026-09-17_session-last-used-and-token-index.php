<?php
    use ZubZet\Framework\Database\Migration\Migration;

    class Migration_2026_09_17_session_last_used_and_token_index extends Migration {

        public function execute(): void {
            $table = $this->tableAlter("z_logintoken");

            if(!$table->hasColumn("last_used")) {
                $table->addColumn("last_used", "datetime", [
                    "notnull" => false,
                    "columnDefinition" => "TIMESTAMP NULL DEFAULT NULL AFTER `expires_at`",
                ]);
            }

            if(!$table->hasIndex("token")) {
                $table->addIndex(["token"], "token");
            }
        }
    }
?>
