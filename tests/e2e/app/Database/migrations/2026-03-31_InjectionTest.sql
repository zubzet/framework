CREATE TABLE `injection_test` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `value_1` VARCHAR(255) NOT NULL,
    `value_2` VARCHAR(255) NOT NULL
);

INSERT INTO `injection_test` (`value_1`, `value_2`) VALUES
('normal_value', 'normal_value');