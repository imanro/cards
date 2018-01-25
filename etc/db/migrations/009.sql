ALTER TABLE `user` ADD COLUMN `affiliate_code` varchar(10) NULL;
ALTER TABLE `user` ADD COLUMN `inviter_user_id` int(10) UNSIGNED NULL;

ALTER TABLE `user`
  ADD CONSTRAINT `user_inviter_user_id_fkey` FOREIGN KEY (`inviter_user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `task_message` ADD COLUMN `free` enum('1', '0') NOT NULL DEFAULT '0';

-- user balance
CREATE TABLE `user_balance_transaction` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`type` enum('income', 'expense') NOT NULL DEFAULT 'income',
	`value` int unsigned NOT NULL,
	`subject` enum('affiliate_invite') NULL,
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


ALTER TABLE `mail_template` ADD COLUMN `code` varchar(100) NULL;
ALTER TABLE `mail_template` ADD COLUMN `system` enum('1', '0') NOT NULL DEFAULT '0';

-- System templates
INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('user_template_1', '1', 2, 'Поздравляю Вас с Днем вашей свадьбы!', 'Поздравляю Вас с Днем вашей свадьбы!', 'Уважаемые %ИМЯ% и %ИМЯ РОДСТВЕННИКА%!
Я искренне поздравляю Вас с днем Вашей свадьбы.
Вы – прекрасная пара, и мне доставило огромное удовольствие работать с Вами.
Я желаю Вам многих лет крепкой любви, дружбы и взаимных радостей.

С уважением,
%МОЕ ИМЯ% %МОЯ ФАМИЛИЯ%');

INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('user_template_2', '1', 2, 'С Днем Рождения, %ИМЯ%', 'С Днем Рождения, %ИМЯ%', '%ИМЯ%!
Я поздравляю тебя с Днем твоего Рождения!
Желаю тебе крепкого здоровья и быстрого и эффективного достижения поставленных целей!

%МОЕ ИМЯ%');
INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('signup_email_confirm', '1', 2, 'Регистрация на сайте %НАЗВАНИЕ САЙТА%', 'Регистрация на сайте %НАЗВАНИЕ САЙТА%', 'Для завершения регистрации, перейдите, пожалуйста, по ссылке:

%ССЫЛКА%');
INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('password_reset_ask', '1', 2, 'Сброс пароля на сайте %НАЗВАНИЕ САЙТА%', 'Сброс пароля на сайте %НАЗВАНИЕ САЙТА%', 'Чтобы сбросить пароль, перейдите, пожалуйста, по ссылке:

%ССЫЛКА%');
INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('affiliate_invite', '1', 2, 'Посмотри, это полезная штука для автоматических поздравлений твоих клиентов', 'Посмотри, это полезная штука для автоматических поздравлений твоих клиентов', 'Привет! Посмотри на этот сервис – очень полезен для позравлений твои клиентов автоматически. Регистрируйся по ссылке:

%ССЫЛКА%

%МОЕ ИМЯ% <%МОЙ E-MAIL%>');
