-- Single InnoDB row locked by a second connection in
-- DatabaseRetryProbeController to force a retryable lock-wait timeout (1205)
-- on the framework connection. Driven by tests/cypress/e2e/database/retry.cy.js.
-- InnoDB is required for row-level locking.
CREATE TABLE `z_test_retry` (
    `id` INT PRIMARY KEY NOT NULL,
    `v` INT NOT NULL
) ENGINE=InnoDB;

INSERT INTO `z_test_retry` (`id`, `v`) VALUES (1, 0);
