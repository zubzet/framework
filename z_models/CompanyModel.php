<?php

    class CompanyModel extends z_model {

        function getInfo() {
            $sql = "SELECT * FROM `companyinfo` WHERE `active`=1 ORDER BY `created` DESC LIMIT 1";
            $this->exec($sql);
            return $this->resultToLine();
        }

        function deleteAll() {
            $sql = "UPDATE `companyinfo` SET `active`=0";
            $this->exec($sql);
            $this->logAction($this->getLogCategoryIdByName("Company"), "Company information disabled");
        }

        function add($name, $email, $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $web, $phone, $mobile_phone, $fax) {
            $sql = "INSERT INTO `companyinfo`(`name`, `email`, `addr_country`, `addr_state`, `addr_city`, `addr_zip`, `addr_street`, `addr_street_number`, `web`, `phone`, `mobile_phone`, `fax`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->exec($sql, "ssssssssssss", $name, $email, $addr_country, $addr_state, $addr_city, $addr_zip, $addr_street, $addr_street_number, $web, $phone, $mobile_phone, $fax);

            //Log
            $this->logAction($this->getLogCategoryIdByName("company"), "Company information added");
        }

    }

?>