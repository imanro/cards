<?php
// allowing short pages names
Route::set('page', 'page/<slug>')
	->defaults(array(
		'controller' => 'page',
		'action'     => 'route',
		// default module prefix for controller (\Frontend\Controller\{name})
		'module'     => 'frontend',
	));

Route::set('affiliate_signup', '<code>', array('code' => 'p[0-9]+'))
	->defaults(array(
		'controller' => 'auth',
		'action'     => 'signup',
		// default module prefix for controller (\Frontend\Controller\{name})
		'module'     => 'frontend',
	));

Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(array(
		'controller' => 'index',
		'action'     => 'index',
		// default module prefix for controller (\Frontend\Controller\{name})
		'module'     => 'frontend',
	));

