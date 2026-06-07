ALTER TABLE `z_user`
    ADD COLUMN IF NOT EXISTS `password_scheme` VARCHAR(32) DEFAULT 'legacy'
    COMMENT 'hashing scheme only' AFTER `password`;

UPDATE `z_user`
    SET `password_scheme` = NULL
    WHERE `password` IS NULL OR `password` = '';

ALTER TABLE `z_user`
    ADD COLUMN IF NOT EXISTS `last_password_rehash_at` TIMESTAMP NULL DEFAULT NULL
    COMMENT 'when the stored password hash was last (re)hashed' AFTER `password_scheme`;

-- Idempotent backfill: only seed rows that have a password and are not yet stamped,
-- using the account creation time as a lower bound on the hash's age. Passwordless
-- rows (SSO/invite) and rows already stamped by a login rehash are left untouched.
UPDATE `z_user`
    SET `last_password_rehash_at` = `created`
    WHERE `last_password_rehash_at` IS NULL
      AND `password` IS NOT NULL
      AND `password` <> '';
