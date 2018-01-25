<?php

return array(
	'manro_sandbox' => array(
		'type'       => 'PDO',
		'identifier' => '`', // important stuff
		'connection' => array(
			'dsn'        => 'mysql:host=localhost;dbname=cards',
			'username'   => 'cards',
			'password'   => 'cards',
			'persistent' => FALSE,
			'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"),
		),
	),
	'staging' => array(
		'type'       => 'PDO',
		'identifier' => '`', // important stuff
		'connection' => array(
			'dsn'        => 'mysql:host=localhost;dbname=manro_cards',
			'username'   => 'manro_cards',
			'password'   => 'manro_cards',
			'persistent' => FALSE,
			'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"),
		),
	),
	'production' => array(
		'type'       => 'PDO',
		'identifier' => '`', // important stuff
		'connection' => array(
			'dsn'        => 'mysql:host=localhost;dbname=cards',
			'username'   => 'cards',
			'password'   => '123456',
			'persistent' => FALSE,
			'options' => array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"),
		),
	)
);