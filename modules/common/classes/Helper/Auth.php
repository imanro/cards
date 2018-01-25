<?php

namespace Common\Helper;

use Auth as LibAuth;
use Common\Model\User;

class Auth {

	/**
	 * @return \Common\Model\User
	 */
	public static function get_current_user()
	{
		$user = LibAuth::instance()->get_user();
		if(!$user || !$user->loaded()){
			$user = new User();
			$user->id = User::ID_UNDEFINED;
		}

		return $user;
	}
}