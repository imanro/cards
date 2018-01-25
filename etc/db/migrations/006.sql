ALTER TABLE `user` ADD COLUMN `mail_smtp_port`	varchar(5) NULL;
ALTER TABLE `user` ADD COLUMN `mail_smtp_start_tls` enum('1', '0') NULL;
ALTER TABLE `user` ADD COLUMN `mail_smtp_tls_ssl` enum('1', '0') NULL;
