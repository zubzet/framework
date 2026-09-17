ALTER TABLE `z_logintoken`
    ADD INDEX IF NOT EXISTS `token` (`token`);
