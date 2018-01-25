<?php
namespace Common\User;
use Common\Model\User;
use Common\Model\Timezone;
use ORM;
use Database;
use DateTimeZone;

class Environment {

	public static function init_user_environment(User $user)
	{
		self::_init_user_mail_templates($user);
	}

	public static function set_user_preferences(User $user)
	{
		self::_set_user_timezone($user);
	}

	protected static function _init_user_mail_templates(User $user)
	{
		$model = ORM::factory('Common\Model\Mail\Template');
		$templates = $model->create_default_templates($user);

		if (is_array($templates))
		{
			foreach ($templates as $template)
			{
				$template->save();
			}
		}
	}

	protected static function _set_user_timezone(User $user)
	{
		if ($user->timezone)
		{
			date_default_timezone_set($user->timezone->name);

			// dealing with db: get timezone offset with DLS
			$offset = Timezone::format_offset(Timezone::get_timezone_offset($user->timezone->name));
			Database::instance()->query(NULL, 'SET time_zone=' . Database::instance()->quote($offset) . ';');
		}
		else
		{
			throw new \Kohana_Exception('Unable to find user timezone');
		}
	}
}