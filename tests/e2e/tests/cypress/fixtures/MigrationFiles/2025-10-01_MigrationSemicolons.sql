-- A comment line; its semicolon does not end a statement
# A hash comment; with a semicolon
CREATE TABLE `migration_semicolons` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    -- An indented comment; inside the statement
    `value` VARCHAR(255) NOT NULL
);

INSERT INTO `migration_semicolons` (`value`) VALUES ('first');
-- A comment; between two statements
INSERT INTO `migration_semicolons` (`value`) VALUES ('second');
-- A trailing comment; after the last statement

INSERT INTO `migration_semicolons` (`value`) VALUES ('third; in a string'); -- trailing; comment
INSERT INTO `migration_semicolons` (`value`) VALUES ("fourth; \"quoted\"; and 'it''s'") /* block; comment */;
/* A block comment;
   over several lines; */
INSERT INTO `migration_semicolons` (`value`) VALUES ('fifth; it''s; escaped\'; still one');
