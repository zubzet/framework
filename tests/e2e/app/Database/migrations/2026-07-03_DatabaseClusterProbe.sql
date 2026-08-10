-- Rows written through the application endpoint and counted directly on the
-- other Galera nodes to assert replication. Driven by
-- tests/cypress/e2e/database/cluster.cy.js and failover.cy.js.
CREATE TABLE `test_cluster` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `marker` VARCHAR(64) NOT NULL
) ENGINE=InnoDB;
