-- ----------------------
-- Role
CREATE TABLE `role` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `description` varchar(255) NOT NULL,
  CONSTRAINT `role_pkey` PRIMARY KEY  (`id`),
  UNIQUE KEY `role_name_ukey` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


-- ----------------------
-- Timezone
CREATE TABLE `timezone` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `country` varchar(100) NOT NULL,
            `utc` char(5) NOT NULL,
            `dst` char(1) NOT NULL,
            `code` varchar(12) NOT NULL,
        CONSTRAINT `timezone_pkey` PRIMARY KEY (`id`),
        CONSTRAINT `timezone_name_ukey` UNIQUE KEY(`name`)

) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Time Zones';

-- ----------------------
-- User
CREATE TABLE `user` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(254) NOT NULL,
  `password` varchar(64) NOT NULL,
  `first_name` varchar(255) NULL,
  `last_name` varchar(255) NULL,
  `logins` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_login` timestamp NULL,
  `mail_smtp_host` varchar(255) NULL,
  `mail_smtp_port`	varchar(5) NULL,
  `mail_smtp_start_tls` enum('1', '0') NULL,
  `mail_smtp_tls_ssl` enum('1', '0') NULL,
  `mail_smtp_user` varchar(150) NULL,
  `mail_smtp_password` varchar(64) NULL,
  `password_reset_hash` varchar(100) NULL,
  `mail_confirm_hash` varchar(100) NULL,
  `affiliate_code` varchar(10) NULL,
  `timezone_id` int UNSIGNED NOT NULL DEFAULT 300,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inviter_user_id` int(10) UNSIGNED NULL,

  CONSTRAINT `user_pkey` PRIMARY KEY  (`id`),
  UNIQUE KEY `user_email_ukey` (`email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user`
	ADD CONSTRAINT `user_timezone_id_fkey` FOREIGN KEY (timezone_id) REFERENCES `timezone`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE `user`
  ADD CONSTRAINT `user_inviter_user_id_fkey` FOREIGN KEY (`inviter_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ----------------------
-- Role-to-user many_many
CREATE TABLE `role_user` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  CONSTRAINT `role_user_pkey` PRIMARY KEY  (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `role_user_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------
-- User token 
CREATE TABLE `user_token` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `user_agent` varchar(40) NOT NULL,
  `token` varchar(40) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires` timestamp NULL,
  CONSTRAINT `user_token_pkey` PRIMARY KEY  (`id`),
  UNIQUE KEY `user_token_ukey` (`token`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user_token`
  ADD CONSTRAINT `user_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

CREATE INDEX `user_token_expires_idx` ON user_token (`expires`) USING hash;

-- ----------------------
-- Sessions table
DROP TABLE IF EXISTS `session`;

CREATE TABLE `session` (
  `id` VARCHAR(24) NOT NULL,
  `last_active` INT UNSIGNED NOT NULL,
  `contents` TEXT NOT NULL,
  CONSTRAINT `session_pkey` PRIMARY KEY (`id`)

) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Sessions';

-- ~~ INDEXES ~~
CREATE INDEX `session_last_active_idx` ON session(`last_active`) USING hash;

-- ----------------------
-- mail templates
CREATE TABLE `mail_template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) NULL,
  `system` enum('1', '0') NOT NULL DEFAULT '0',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int UNSIGNED NOT NULL,
  CONSTRAINT `mail_template_pkey` PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `mail_template`
  ADD CONSTRAINT `mail_template_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------
-- task_message
CREATE TABLE `task_message` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `recipient_name` varchar(255) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `second_recipient_name` varchar(255) NULL,
  `mail_subject` varchar(255) NOT NULL DEFAULT '',
  `mail_body` text NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `exec_date` date NULL,
  `active` enum('1', '0') NOT NULL DEFAULT '1',
  `repeated` enum('1', '0') NOT NULL DEFAULT '1',
  `repeat_interval_measure` enum('year', 'month', 'week', 'day') NULL DEFAULT 'year',
  `repeat_interval` int NULL DEFAULT '1',
  `hidden` enum('1', '0') NOT NULL DEFAULT '0',
  `free` enum('1', '0') NOT NULL DEFAULT '0',
  CONSTRAINT `task_message_pkey` PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `task_message`
  ADD CONSTRAINT `task_message_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------
-- mail log item
CREATE TABLE `task_message_delivery` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `state` enum('success', 'failure') NOT NULL DEFAULT 'success',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `task_message_id` int unsigned NOT NULL,
  `error_text` varchar(255) NULL,
  CONSTRAINT `mail_log_item_pkey` PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `task_message_delivery`
  ADD CONSTRAINT `task_message_delivery_task_message_id_fkey` FOREIGN KEY (`task_message_id`) REFERENCES `task_message` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------
-- Page
CREATE TABLE `page` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `text` text NULL,
  `is_hidden` enum('1', '0') NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `page_pkey` PRIMARY KEY  (`id`),
  CONSTRAINT `page_slug_ukey` UNIQUE KEY(`slug`)

) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ----------------------
-- Balance
CREATE TABLE `user_balance` (
	`user_id` int unsigned NOT NULL,
	`value` int unsigned NOT NULL,
	
	CONSTRAINT `user_balance_pkey` PRIMARY KEY  (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user_balance`
  ADD CONSTRAINT `user_balance_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- ----------------------
-- User Balance Transaction

CREATE TABLE `user_balance_transaction` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`type` enum('income', 'expense') NOT NULL DEFAULT 'income',
	`value` int unsigned NOT NULL,
	`subject` enum('affiliate_invite') NULL, -- + INDEX
	`resource` varchar(100) NULL,
	`user_id` int unsigned NOT NULL,
	`create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	
	CONSTRAINT `user_balance_transaction_pkey` PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

ALTER TABLE `user_balance_transaction`
  ADD CONSTRAINT `user_balance_transaction_user_id_fkey` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;


-- ----------------------
-- Settings section
CREATE TABLE `config` (
	`config_key` varchar(100) NOT NULL,
	`group_name` varchar(100) NOT NULL,
	`config_value` varchar(255) NOT NULL DEFAULT '',
	
	CONSTRAINT `config_pkey` PRIMARY KEY  (`config_key`, `group_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

