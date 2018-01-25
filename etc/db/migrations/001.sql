CREATE TABLE IF NOT EXISTS `page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `text` text NULL,
  `is_hidden` enum('1', '0') NOT NULL DEFAULT '0',
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `page_pkey` PRIMARY KEY  (`id`),
  CONSTRAINT `page_slug_ukey` UNIQUE KEY(`slug`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


INSERT INTO `page` (`id`, `title`, `slug`, `text`, `is_hidden`) VALUES (NULL, 'Описание', 'description', NULL, '1');
INSERT INTO `page` (`id`, `title`, `slug`, `text`, `is_hidden`) VALUES (NULL, 'Частые вопросы', 'faq', NULL, '0');
INSERT INTO `page` (`id`, `title`, `slug`, `text`, `is_hidden`) VALUES (NULL, 'Контакты', 'contacts', NULL, '0');
INSERT INTO `page` (`id`, `title`, `slug`, `text`, `is_hidden`) VALUES (NULL, 'Полезная информация', 'info', NULL, '0');
INSERT INTO `page` (`id`, `title`, `slug`, `text`, `is_hidden`) VALUES (NULL, 'Условия использования', 'agreement', NULL, '1');
