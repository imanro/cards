-- ----------------------
-- Balance
CREATE TABLE `user_balance` (
	`user_id` int unsigned NOT NULL,
	`value` int unsigned NOT NULL,
	
	CONSTRAINT `user_balance_pkey` PRIMARY KEY  (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user_balance`
  ADD CONSTRAINT `user_balance_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
