<?php

namespace Frontend\Renderer;

use Kohana;
use Frontend\Module;
use View;
use Flasher;
use Auth;
use Request;
use Route;

trait MainTemplate {

	public static $view_layout = 'layouts/main';

	public static $view_header = 'partials/header';

	public static $view_messages = 'partials/messages';

	public static $view_menu_navigation = 'partials/menu-navigation';

	public static $template_css = array();

	public static $template_js = array();

	public static $template;

	public static function init_template()
	{
		self::$template = View::factory(self::$view_layout, array(), Module::$name);
		self::$template->title = '';
		self::$template->content = '';
		self::$template->messages = '';
		self::$template->header = '';
		self::$template->menu_navigation = '';
	}

	protected static function _render_header($get_user = TRUE)
	{
		if(Request::current()){
			$active_navigation_slug = Request::current()->param('id');
		} else {
			$active_navigation_slug = NULL;
		}
		$template_save = self::$template;

		$menu_pages = Request::factory(Route::get('default')->uri(array(
				'controller' => 'page',
				'action' => 'menu-list',
				'id' => $active_navigation_slug,
				'layout' => 0,
				'user_session' => 0,
			)))->execute();

		// saving and restoring original template (due to static properties subrequest clears all before initialized variables in self::$template)
		self::$template = $template_save;

		if($get_user){
			$user = Auth::instance()->get_user();
			$site_name = Kohana::$config->load('settings')->get('site_name');
		} else {
			$user = NULL;
			$site_name = 'Cards';
		}
		return View::factory(self::$view_header, array('user' => $user, 'menu_pages' => $menu_pages, 'site_name' => $site_name), Module::$name);
	}

	protected static function _render_messages()
	{
		return View::factory(self::$view_messages, array('messages' => Flasher::get_messages()), Module::$name);
	}

	protected static function _render_menu_navigation()
	{
		$user = Auth::instance()->get_user();
		return View::factory(self::$view_menu_navigation, array('menu_navigation' => self::_get_menu_navigation(), 'user' => $user));
	}

	protected static function _get_menu_navigation()
	{
		return array();
	}

	protected static function add_template_assets()
	{
		self::$template->set('css', self::$template_css);
		self::$template->set('js', self::$template_js);
	}
}