CREATE TABLE `duplicate` (
	`id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `value` VARCHAR(255) NOT NULL
);

INSERT INTO `duplicate` (`value`) VALUES
('UniqueText'),
('1111-11-11'),
('99'),
('Unique@gmail.com'),
('https://www.unique.de');

CREATE TABLE `model_test_insert` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `value` VARCHAR(255) NOT NULL
);

CREATE TABLE `model_test_select` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `value` VARCHAR(255) NOT NULL
);

INSERT INTO `model_test_select` (`value`) VALUES
('ABC'), ('CDE'), ('EFG');

CREATE TABLE `model_test_lastid` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `value` VARCHAR(255) NOT NULL
);