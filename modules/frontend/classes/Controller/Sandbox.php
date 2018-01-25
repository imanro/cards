<?php

namespace Frontend\Controller;


use Common\Messenger;
use ORM;
use Common\Model\User;
use Route;
use URL;

class Sandbox extends FrontendController {
	public function action_signup()
	{
		$user = ORM::factory('Common\Model\User');
		/* @var $user User */
		$user->email = 'roman.denisov@gmail.com';

		$link = URL::site(Route::get('default')->uri(array(
				'action' => 'mail_confirm',
				'controller' => 'auth',
				'hash' => md5(uniqid())
		)), 'http');

		Messenger::notify_signup_email_confirm($user, $link);
	}
}