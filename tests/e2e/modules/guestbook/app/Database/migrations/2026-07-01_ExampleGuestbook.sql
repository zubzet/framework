CREATE TABLE `guestbook_entries` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
    `author` VARCHAR(64) NOT NULL,
    `message` VARCHAR(255) NOT NULL
);

INSERT INTO `guestbook_entries`(`author`, `message`) VALUES
('Migration', 'Guestbook is ready');
