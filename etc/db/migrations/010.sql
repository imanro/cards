INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('mailing_upcoming_messages', '1', 2, 'План поздравлений на 10 дней', 'План поздравлений на 10 дней', '<p>Здравствуйте, %МОЕ ИМЯ%.</p>

<p>Вас беспокоит администрация %НАЗВАНИЕ САЙТА%</p>
<p>На ближайшие 10 дней у Вас запланированы следующие поздравления. Если вдруг Вы хотите купить подарок или поздравить кого-то из списка лично, то включите это в Ваш список запланированных дел.</p>

%РАСПИСАНИЕ%

<address>С уважением,<br/>
Администрация %НАЗВАНИЕ САЙТА%</address>
');

INSERT INTO `mail_template` (`code`, `system`, `user_id`, `name`, `subject`, `body`) VALUES('alert_balance', '1', 2, 'У вас осталось L поздравлений', 'У вас осталось %КОЛИЧЕСТВО% поздравлений', '<p>Позовите друзей и коллег, чтобы увеличить кол-во доступных писем.</p> 

<p>Ваша партнерская ссылка: %ССЫЛКА%.</p> 
<p>%ПАРТНЕРСКАЯ ПРОГРАММА РАЗДЕЛ ССЫЛКА%</p>

<address>С уважением,<br/>
Администрация %НАЗВАНИЕ САЙТА%</address>
');
