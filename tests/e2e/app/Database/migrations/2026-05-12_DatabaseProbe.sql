-- Tables used by tests/cypress/e2e/database/interaction.cy.js to exercise
-- Interaction::mergeAsGroup (all 4 path combinations) and
-- countTableEntries (happy + empty).
CREATE TABLE `z_test_grouping` (
    `group_id` INT NOT NULL,
    `label` CHAR(1) NOT NULL,
    `val` INT NOT NULL
);

INSERT INTO `z_test_grouping` (`group_id`, `label`, `val`) VALUES
    (1, 'A', 10),
    (1, 'B', 20),
    (2, 'C', 30);

CREATE TABLE `z_test_empty` (
    `id` INT PRIMARY KEY AUTO_INCREMENT NOT NULL
);
