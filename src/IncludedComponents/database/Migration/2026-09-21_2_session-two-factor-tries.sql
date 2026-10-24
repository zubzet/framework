ALTER TABLE `z_logintoken`
  ADD COLUMN IF NOT EXISTS `remaining_two_factor_tries` TINYINT UNSIGNED NOT NULL DEFAULT 5 AFTER `last_two_factor`;
