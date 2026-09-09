ALTER TABLE `z_file`
    ADD COLUMN IF NOT EXISTS `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `z_file` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `z_file`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX IF NOT EXISTS `uuid` (`uuid`, `active`);

ALTER TABLE `z_user`
    ADD COLUMN IF NOT EXISTS `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `z_user` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `z_user`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX IF NOT EXISTS `uuid` (`uuid`, `active`);

ALTER TABLE `z_organization`
    ADD COLUMN IF NOT EXISTS `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `z_organization` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `z_organization`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX IF NOT EXISTS `uuid` (`uuid`, `active`);

ALTER TABLE `z_role`
    ADD COLUMN IF NOT EXISTS `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `z_role` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `z_role`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX IF NOT EXISTS `uuid` (`uuid`, `active`);

ALTER TABLE `z_logintoken`
    ADD COLUMN IF NOT EXISTS `uuid` UUID NULL DEFAULT NULL AFTER `id`;

UPDATE `z_logintoken` SET `uuid` = UUID_v7() WHERE `uuid` IS NULL;

ALTER TABLE `z_logintoken`
    MODIFY COLUMN `uuid` UUID NOT NULL DEFAULT UUID_v7(),
    ADD INDEX IF NOT EXISTS `uuid` (`uuid`, `active`);
