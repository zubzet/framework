-- Table: z_email_verify
CREATE TABLE `z_email_verify` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `user` INT NOT NULL,
  `end` DATETIME NOT NULL,
  `active` INT NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_file
CREATE TABLE `z_file` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `reference` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `extension` VARCHAR(255) NOT NULL,
  `size` INT NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_interaction_log
CREATE TABLE `z_interaction_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `categoryId` INT NOT NULL,
  `userId` INT DEFAULT NULL,
  `userId_exec` INT DEFAULT NULL,
  `text` MEDIUMTEXT DEFAULT NULL,
  `value` MEDIUMTEXT DEFAULT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_interaction_log_category
CREATE TABLE `z_interaction_log_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_interaction_log_category
INSERT INTO `z_interaction_log_category` (`id`, `name`, `created`) VALUES
(1, 'view', '2021-02-04 16:08:15'),
(2, 'login', '2021-02-05 21:50:27'),
(3, 'logout', '2021-02-07 14:03:20'),
(4, 'user', '2021-02-07 14:44:57'),
(5, 'PasswordResetRequest', '2021-02-10 14:04:49'),
(6, 'PasswordReset', '2021-02-10 14:05:27'),
(7, 'SecurityAlert', '2021-02-10 14:24:00'),
(8, 'loginas', '2021-02-16 17:18:21'),
(9, 'PasswordChanged', '2021-07-01 17:11:06'),
(10, 'resterror', '2021-07-16 02:38:47'),
(11, 'PasswordCreated', '2023-07-17 07:53:49');

-- Table: z_language
CREATE TABLE `z_language` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `nativeName` VARCHAR(255) NOT NULL,
  `value` VARCHAR(255) NOT NULL
);

-- Table: z_language
INSERT INTO `z_language` (`id`, `name`, `nativeName`, `value`) VALUES
(1, 'English', 'English', 'EN'),
(2, 'German', 'Deutsch', 'DE');
UPDATE `z_language` SET `id`=0 WHERE `name`='German';

-- Table: z_logintoken
CREATE TABLE `z_logintoken` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `userId` INT NOT NULL,
  `userId_exec` INT NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_logintry
CREATE TABLE `z_logintry` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `userId` INT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_login_too_many_tries
CREATE TABLE `z_login_too_many_tries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `userId` INT NOT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_password_reset
CREATE TABLE `z_password_reset` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `userId` INT NOT NULL,
  `refId` VARCHAR(255) NOT NULL,
  `reason` enum('create','change','forgot') NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_role
CREATE TABLE `z_role` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `name` VARCHAR(255) DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_role_permission
CREATE TABLE `z_role_permission` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `role` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_uniqueref
CREATE TABLE `z_uniqueref` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `ref` VARCHAR(255) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

-- Table: z_user
CREATE TABLE `z_user` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `salt` VARCHAR(255) DEFAULT NULL,
  `languageId` INT NOT NULL DEFAULT 0,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `verified` TIMESTAMP NULL DEFAULT NULL
);

-- Table: z_user_role
CREATE TABLE `z_user_role` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `role` INT NOT NULL,
  `user` INT NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
);