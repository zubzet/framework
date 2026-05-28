<?php 

    class QueryBuilderModel extends z_model {

        public function selectUserExtended() {
            $query = $this->dbSelect("u.id, u.email", "z_user u")
                            ->where(["u.id" => 1])
                            ->where(["u.email" => "admin@zierhut-it.de"]);

            return $this->exec($query)->resultToArray();
        }

        public function selectUserJoin() {
            $query = $this->dbSelect("u.id, u.email, r.name", "z_user u")
                            ->join([
                                "ur" => [
                                    "table" => "z_user_role",
                                    "conditions" => "ur.user = u.id",
                                    "type" => "LEFT"
                                ],
                                "r" => [
                                    "table" => "z_role",
                                    "conditions" => "ur.role = r.id",
                                    "type" => "LEFT"
                                ]
                            ])
                            ->where(["u.id" => 1]);

            return $this->exec($query)->resultToArray();
        }

        public function selectUserLike() {
            $query = $this->dbSelect("u.id, u.email", "z_user u")
                            ->where(["u.email LIKE" => "%admin%"]);

            return $this->exec($query)->resultToArray();
        }

        public function selectUserLT() {
            $query = $this->dbSelect("u.id, u.email", "z_user u")
                            ->where(["u.id <" => 3]);

            return $this->exec($query)->resultToArray();
        }

        public function selectUserIn() {
            $query = $this->dbSelect("u.id, u.email", "z_user u")
                            ->where(["u.id IN" => [1, 2]]);
            return $this->exec($query)->resultToArray();
        }

        public function selectUserORAND() {
            $query = $this->dbSelect("u.id, u.email", "z_user u")
                            ->where([
                                "OR" => [
                                    "u.id" => 1,
                                    "u.email LIKE" => "%support%"
                                ],
                                "AND" => [
                                    "u.languageId" => 0
                                ]
                            ]);
            return $this->exec($query)->resultToArray();
        }

        public function selectUserLimit() {
            $query = $this->dbSelect("u.id, u.email", ["u" => "z_user"])
                            ->limit(2)
                            ->page(2)
                            ->orderAsc("u.id");

            return $this->exec($query)->resultToArray();
        }

        public function selectInsert() {
            $query = $this->dbSelect(["id", "name", "value"], "query_builder_insert");

            return $this->exec($query)->resultToArray();
        }

        public function selectInsertById($id) {
            $query = $this->dbSelect(["id", "name", "value"], "query_builder_insert")
                            ->where(["id" => $id]);

            return $this->exec($query)->resultToLine();
        }

        public function insert() {
            $query = $this->dbInsert("query_builder_insert", [
                "name" => "TestName1",
                "value" => 123
            ]);

            $this->exec($query);

            $query = $this->dbInsert("query_builder_insert", [
                "name" => "TestName2",
                "value" => 456
            ])->values([
                "name" => "TestName3",
                "value" => 789
            ]);

            $this->exec($query);
        }

        public function update() {
            $query = $this->dbUpdate("query_builder_insert", [
                "name" => "UpdatedTestName1",
                "value" => 999,
            ])->where(["id" => 1]);

            $this->exec($query);
        }

        public function delete() {
            $query = $this->dbDelete("query_builder_insert")
                ->where(["id" => 1]);

            $this->exec($query);
        }


        // Before ZubZetValueBinder, values containing :word patterns could confuse the preg_replace('/:\w+/', '?', $sql) step and shift parameter bindings.
        // This was fixed but need to be tested to ensure no regression happens.
        public function injectionTest() {
            $query = $this->dbSelect("*", "injection_test")
                            ->where([
                                "value_1" => ":c1",
                                "value_2" => "normal_value"
                            ]);

            return $this->exec($query)->resultToArray();
        }
    }

?>