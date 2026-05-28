-- Tables used by tests/cypress/e2e/core/response.cy.js to exercise
-- Response::insertDatabase / updateDatabase / insertOrUpdateDatabase
-- (z_probe_form) and Response::doCED (z_probe_ced).
CREATE TABLE `z_probe_form` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `col_a` VARCHAR(64) NOT NULL,
    `col_b` INT NOT NULL,
    `file_id` INT NULL DEFAULT NULL,
    `created_by` INT NOT NULL
);

-- Two payload fields (`name`, `note`) so CED tests can prove the
-- multi-field bind / update path - a single-field CED would let a
-- regression in the field-iteration loop slip through.
CREATE TABLE `z_probe_ced` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(64) NOT NULL,
    `note` VARCHAR(255) NOT NULL DEFAULT '',
    `active` TINYINT NOT NULL DEFAULT 1
);

-- One pre-existing row so doCED edit/delete probes have a fixed target.
INSERT INTO `z_probe_ced` (`id`, `name`, `note`, `active`)
VALUES (1, 'baseline', 'seeded', 1);
