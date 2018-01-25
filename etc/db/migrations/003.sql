ALTER TABLE `task_message` ADD COLUMN `active` enum('1', '0') NOT NULL DEFAULT '1';
ALTER TABLE `task_message` ADD COLUMN `repeated` enum('1', '0') NOT NULL DEFAULT '1';
ALTER TABLE `task_message` ADD COLUMN `repeat_interval_measure` enum('year', 'month', 'week', 'day') NULL DEFAULT 'year';
ALTER TABLE `task_message` ADD COLUMN `repeat_interval` int NULL DEFAULT '1';

ALTER TABLE `task_message` ADD COLUMN  `hidden` enum('1', '0') NOT NULL DEFAULT '0';
RENAME TABLE `mail_log_item` TO `task_message_delivery`;

ALTER TABLE `task_message_delivery`
  DROP FOREIGN KEY `mail_log_item_user_id_fkey`;

ALTER TABLE `task_message_delivery` DROP COLUMN `to`;
ALTER TABLE `task_message_delivery` DROP COLUMN `subject`;
ALTER TABLE `task_message_delivery` DROP COLUMN `body`;
ALTER TABLE `task_message_delivery` DROP COLUMN `user_id`;

TRUNCATE `task_message_delivery`;

ALTER TABLE `task_message_delivery` ADD COLUMN `task_message_id` int unsigned NOT NULL;
ALTER TABLE `task_message_delivery`
  ADD CONSTRAINT `task_message_delivery_task_message_id_fkey` FOREIGN KEY (`task_message_id`) REFERENCES `task_message` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
