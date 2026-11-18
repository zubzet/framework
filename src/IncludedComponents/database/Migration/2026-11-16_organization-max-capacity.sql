ALTER TABLE `z_organization`
    ADD COLUMN IF NOT EXISTS `max_capacity` INT NULL AFTER `groupId`;
