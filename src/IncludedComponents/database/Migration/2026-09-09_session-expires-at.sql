ALTER TABLE `z_logintoken`
    ADD COLUMN IF NOT EXISTS `expires_at` TIMESTAMP NULL DEFAULT NULL AFTER `extended_seconds`;
