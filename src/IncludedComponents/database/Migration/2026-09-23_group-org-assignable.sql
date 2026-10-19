ALTER TABLE `z_role`
    ADD COLUMN IF NOT EXISTS `is_org_assignable` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_group`;
