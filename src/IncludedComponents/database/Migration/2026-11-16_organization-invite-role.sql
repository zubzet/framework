ALTER TABLE `z_organization_invite`
    ADD COLUMN IF NOT EXISTS `roleId` INT NULL AFTER `email`;
