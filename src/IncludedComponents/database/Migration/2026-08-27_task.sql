-- Table: z_task
-- The persistent record of every dispatched task. Rows outlive execution: this
-- is what a frontend polls for status and what remains as history afterwards.
CREATE TABLE IF NOT EXISTS `z_task` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `type` VARCHAR(191) NOT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `payload` LONGTEXT NULL DEFAULT NULL,
  `result` LONGTEXT NULL DEFAULT NULL,
  `error` TEXT NULL DEFAULT NULL,
  `progress` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `attempts` INT NOT NULL DEFAULT 0,
  `userId` INT NULL DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `startedAt` TIMESTAMP NULL DEFAULT NULL,
  `finishedAt` TIMESTAMP NULL DEFAULT NULL
);

ALTER TABLE `z_task`
  ADD INDEX IF NOT EXISTS `status` (`status`),
  ADD INDEX IF NOT EXISTS `userId` (`userId`);

-- Table: z_task_queue
-- Outstanding work only. A row lives exactly as long as its task still needs to
-- run, so an idle installation polls an empty table and the index stays small.
-- Claiming is a conditional UPDATE on `reservedAt`, which is atomic across
-- Galera nodes (a concurrent claim certifies as a conflict and loses).
CREATE TABLE IF NOT EXISTS `z_task_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
  `taskId` INT NOT NULL,
  `queue` VARCHAR(64) NOT NULL DEFAULT 'default',
  `availableAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `reservedAt` TIMESTAMP NULL DEFAULT NULL,
  `reservedBy` VARCHAR(64) NULL DEFAULT NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP()
);

ALTER TABLE `z_task_queue`
  ADD UNIQUE KEY IF NOT EXISTS `taskId` (`taskId`),
  ADD INDEX IF NOT EXISTS `poll` (`queue`, `reservedAt`, `availableAt`);
