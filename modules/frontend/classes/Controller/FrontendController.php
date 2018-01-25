<?php

namespace Frontend\Controller;

use Kohana;
use Controller;
use View;
use Frontend\Module;
use Flasher;
use Auth;
use Route;
use Request;
use Frontend\Renderer\MainTemplate;
use Response;
use Common\User\Environment;
use Acl\Acl;
use Common\Model\Role;
use Acl\Rbac;
use Acl\Rbac\ResourceInterface;
use URL;
use Frontend\Controller\User as UserController;
use Frontend\Controller\Page as PageController;
use Frontend\Controller\TaskMessage as TaskMessageController;
use Frontend\Controller\TaskMessageDelivery as TaskMessageDeliveryController;
use Frontend\Controller\MailTemplate as MailTemplateController;
use Frontend\Controller\Affiliate as AffiliateController;
use Frontend\Controller\Settings as SettingsController;

abstract class FrontendController extends Controller implements ResourceInterface {

	const MESSAGE_INFO = 'info';

	const MESSAGE_ERROR = 'danger';

	const MESSAGE_SUCCESS = 'success';

	const MESSAGE_WARNING = 'warning';

	const PARAM_LAYOUT = 'layout';

	const PARAM_RETURN_URL = 'ret';

	use MainTemplate;

	/**
	 * @var  boolean  auto render template
	 **/
	public $auto_render = TRUE;

	/**
	 * Loads the template [View] object.
	 */
	public function before()
	{
		parent::before();
		// html by default
		$this->response->headers('Content-Type', 'text/html; Charset=' . Kohana::$charset );
		$user_session = is_null($this->request->query('user_session')) || $this->request->query('user_session') == true;
		if ($user_session)
		{
			// checking "user_session" used to prevent recursion during Auth error -> exception -> view -> login() -> auth error -> exception ...
			try
			{
				if (Auth::instance()->logged_in())
				{
					if ($user = Auth::instance()->get_user())
					{
						Environment::set_user_preferences($user);
					}
					// try to log in
				}
			}
			catch (\Exception $e)
			{
				Auth::instance()->logout(TRUE, TRUE);
				throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.auth_error_occured'), NULL, $e);
			}
		}

		if ($this->auto_render === TRUE)
		{
			// Load the template
			self::init_template();
		}
	}

	public function before_action()
	{
		// check runing before each action in stack
		$user = Auth::instance()->get_user();
		if(!$this->_has_access())
		{
			if(!$user || !$user->loaded())
			{
				if (strpos($this->response->headers('Content-Type'), 'application/json') !== FALSE)
				{
					// we have to detect content-type _before_ action execute, some method
					// as access_map() needed
					throw \HTTP_Exception::factory(403, Kohana::message('view', 'message.controller_not_authorized', NULL, Module::$name));
				}
				else
				{
					$this->_add_flash_message('message.auth_needed', Flasher::MESSAGE_WARNING);
					// try to log in
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'auth',
						'action' => 'login',
						self::PARAM_RETURN_URL => URL::site($this->request->uri())
					)));
				}
			}
			else
			{
				throw \HTTP_Exception::factory(403, Kohana::message('view', 'message.controller_access_denied', NULL, Module::$name));
			}
		}
	}

	protected function _has_access()
	{
		$acl = Acl::factory('Rbac');
		/* @var $acl \Acl\Rbac */

		//$action = $this->request->action();
		//var_dump($action);//$this->prepare_access_map($this->access_map()));
		//exit;

		$acl->setup_rules($this->prepare_access_map($this->access_map()));

		$subject = Auth::instance()->get_user();

		if(is_null($subject) || !$subject->loaded()){
			$subject = new Role();
			$subject->name = Role::NAME_GUEST;
		}
		//var_dump($acl->has_access(Rbac::PERMISSION_EXEC, $subject, $this));
		return $acl->has_access(Rbac::PERMISSION_EXEC, $subject, $this);
	}

	/**
	 * @see ResourceInterface
	 */
	public function get_resource_id()
	{
		return self::format_resource_id(self::class, Request::current()->action());
	}

	public static function access_map()
	{
		return array();
	}

	public static function prepare_access_map($map, $class = NULL)
	{
		if(is_null($class)) {
			$class = self::class;
		}

		foreach ($map as &$rules)
		{
			$resources = &$rules[Rbac::MAP_VAR_RESOURCES];

			if (!is_array($resources))
			{
				$resources = array(
					$resources
				);
			}

			foreach ($resources as &$resource)
			{
				$resource = self::format_resource_id($class, $resource);
			}
		}

		return $map;
	}

	public static function format_resource_id($class, $action)
	{
		return $class . '::' . $action;
	}

	/**
	 * Assigns the template [View] as the request response.
	 */
	public function after()
	{

		$layout = is_null($this->request->query(self::PARAM_LAYOUT)) || $this->request->query(self::PARAM_LAYOUT) == true;

		if (strpos($this->response->headers('Content-Type'), 'text/html') === FALSE)
		{
			if(strpos($this->response->headers('Content-Type'), 'application/json') !== FALSE){
				// prevent other data to be appended
				exit($this->response->body());
			}
		}
		else if(!$layout)
		{
			$this->response->body(self::$template->content);
		}
		else if ($this->auto_render === TRUE)
			{
				// rendering content first (this allow to early trow errors without of html doubles)
				self::add_template_assets();
				self::$template->header = $this->_render_header();
				self::$template->messages = $this->_render_messages();
				self::$template->menu_navigation = $this->_render_menu_navigation();
				$content = self::$template->render();
				$this->response->body($content);
		}
		parent::after();
	}

	protected function _add_flash_message($message, $type = Flasher::MESSAGE_SUCCESS, $data = array(), $file = 'view')
	{
		Flasher::add_message(Kohana::message($file, strtr($message, $data), NULL, Module::$name), $type);
	}

	protected function _get_orm_errors_flatten(\ORM_Validation_Exception $e)
	{
		return '<ul><li>' . implode('</li><li>', $e->errors('validation', TRUE, Module::$name)) . '</li></ul>';
	}

	protected static function _get_menu_navigation()
	{
		//var_dump();
		if (Auth::instance()->get_user())
		{
			$menu = array(
				array(
					'title' => Kohana::message('view', 'title.dates', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'task-message',
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'task-message'? 'active': '')))),
					'resource' => array(TaskMessageController::class, 'index')
				),
				array(
					'title' => Kohana::message('view', 'title.mail_templates', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'mail-template'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'mail-template'? 'active': '')))),
					'resource' => array(MailTemplateController::class, 'index'),
				),
				array(
					'title' => Kohana::message('view', 'title.task_message_delivery', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'task-message-delivery'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'task-message-delivery'? 'active': '')))),
					'resource' => array(TaskMessageDeliveryController::class, 'index'),
				),
				array(
					'title' => Kohana::message('view', 'title.pages', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'page'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'page'? 'active': '')))),
					'resource' => array(PageController::class, 'index'),
				),
				array(
					'title' => Kohana::message('view', 'title.users', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'user'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'user'? 'active': '')))),
					'resource' => array(UserController::class, 'index'),
				),
				array(
					'title' => Kohana::message('view', 'title.affiliation_program', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'affiliate'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'affiliate'? 'active': '')))),
					'resource' => array(AffiliateController::class, 'index'),
				),
				array(
					'title' => Kohana::message('view', 'title.settings', NULL, Module::$name),
					'url' => Route::get('default')->uri(array(
						'action' => 'index',
						'controller' => 'settings'
					)),
					'options' => array('class' => implode(' ', array_merge(array(), array(strtolower(Request::current()->controller_name()) == 'settings'? 'active': '')))),
					'resource' => array(SettingsController::class, 'index'),
				),

			);
		}
		else
		{
			$menu = array();
		}

		return $menu;
	}

	/**
	 * @throws \Kohana_Exception
	 * @return \Common\Model\User
	 */
	protected function _get_current_user()
	{
		$user = Auth::instance()->get_user();
		/* @var $user \Common\Model\User */

		if (!$user || !$user->loaded())
		{
			throw new \Kohana_Exception(vsprintf('Unable to use %s without of authorization', array(
				__CLASS__
			)));
			return NULL;
		}
		else
		{
			return $user;
		}
	}

	protected function _init_json_response()
	{
		\Kohana_Exception::$error_view_content_type = 'application/json';
		$this->response->headers('Content-Type', 'application/json; charset='.Kohana::$charset);
	}
}
