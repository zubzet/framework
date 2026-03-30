ALTER TABLE `z_logintoken`
    ADD COLUMN `extended_seconds` INT NULL DEFAULT NULL AFTER `userId_exec`,
    ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `extended_seconds`;
