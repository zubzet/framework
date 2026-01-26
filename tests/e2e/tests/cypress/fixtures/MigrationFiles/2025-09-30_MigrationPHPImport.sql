CREATE TABLE `migration_php_import` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL
);

CREATE TABLE `migration_php_remove` (
    `id` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
    `description` varchar(500) NOT NULL
);