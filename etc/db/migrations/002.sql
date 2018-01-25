ALTER TABLE `user` ADD COLUMN `mail_smtp_host` varchar(255) NULL;
ALTER TABLE `user` ADD COLUMN `mail_smtp_user` varchar(150) NULL;
ALTER TABLE `user` ADD COLUMN `mail_smtp_password` varchar(64) NULL;
ALTER TABLE `user` ADD COLUMN `password_reset_hash` varchar(100) NULL;
ALTER TABLE `user` ADD COLUMN `mail_confirm_hash` varchar(100) NULL;
ALTER TABLE `user` CHANGE `last_login` `last_login` timestamp NULL;
ALTER TABLE `user` ADD COLUMN `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `user_token` CHANGE `created` `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
