<?php

namespace Frontend\Controller;

use Kohana;
use View;
use Request;
use Route;
use Frontend\Module;
use Auth;
use Common\Model\Role;
use Acl\Rbac;

class Index extends  FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'welcome',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'dashboard',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			)
		);
	}

	public function action_index()
	{
		// TODO: conditions to auth/unauth users
		$user = Auth::instance()->get_user();

		if(!$user || !$user->loaded()){
			return $this->execute_action('welcome');
		} else {
			return $this->execute_action('dashboard');
		}
	}

	public function action_welcome()
	{
		self::$template->title = Kohana::$config->load('settings')->get('site_name');

		$template_keep = self::$template;

		$description = Request::factory(Route::get('page')->uri(array(
				'controller' => 'page',
				'action' => 'view',
				'slug' => 'description',
				'layout' => 0
		)))->execute();

		$signup_form = Request::factory(Route::get('default')->uri(array(
				'controller' => 'auth',
				'action' => 'signup',
				'layout' => 0
			)))->execute();

			$login_form = Request::factory(Route::get('default')->uri(array(
				'controller' => 'auth',
				'action' => 'login',
				'layout' => 0
			)))->execute();

			self::$template = $template_keep; // due to static members and subrequests

		self::$template->content = View::factory('index/welcome', array(
			'description' => $description,
			'signup_form' => $signup_form,
			'login_form' => $login_form,
		), Module::$name);
	}

	public function action_dashboard()
	{
		self::$template->content = '';
	}
}
