ALTER TABLE `z_logintoken`
    ADD UNIQUE INDEX IF NOT EXISTS `token` (`token`);
