ALTER TABLE `z_organization_invite`
    ADD COLUMN IF NOT EXISTS `roleId` INT NULL AFTER `email`;

ALTER TABLE `z_organization`
    ADD COLUMN IF NOT EXISTS `max_capacity` INT NULL AFTER `groupId`;
