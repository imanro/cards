<?php

namespace Frontend\Controller;

use Form_FormAbstract;
use Frontend\Module;
use Request;
use View;
use Auth as Ko_Auth;
use HTTP_Request;
use Kohana;
use Flasher;
use Route;
use ORM;
use Common\Model\Role;
use Acl\Rbac;
use URL;
use Common\Helper\Auth as AuthHelper;

class Auth extends FrontendController {

	public static function access_map()
	{
		return array(
			//
			array(
				Rbac::MAP_VAR_RESOURCES => 'login',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'logout',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'signup',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_GUEST, Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'mail-confirm',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_GUEST, Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'password-reset-ask',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_GUEST, Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'password-reset',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_GUEST, Role::NAME_LOGIN)
			),
		);

	}

	public function action_login()
	{
		try {
			$user = AuthHelper::get_current_user();
		} catch( \Exception $e ) {
			$user = NULL;
		}

		if($user && $user->loaded()){
			return $this->redirect(Route::get('default')->uri(array(
							'action' => 'index',
							'controller' => 'index'
						)));
		}
		$request = Request::current();
		//		session_destroy();
		$is_layout = is_null($this->request->query('layout')) || $this->request->query('layout') == true;

		$title = Kohana::message('view', 'title.login', NULL, Module::$name);

		self::$template->title = $title;

		$form_model = Form_FormAbstract::factory('Frontend\Form\User\Login');

		$form = $form_model->get_form(array(
			'*'
		));

		$params = $this->request->query();
		unset($params[self::PARAM_LAYOUT]);
		$form->attr('action', $this->request->url() . '?' . http_build_query($params));

		if ($this->request->method() == HTTP_Request::POST)
		{

			$form_model->values($form->val());
			if ($form->load($request->post())->validate())
			{
				try
				{
					if ($retval = Ko_Auth::instance()->login($form->email->val(), $form->password->val(), $form->remember_me->val()))
					{
						;
					}
					else
					{
						$form->password->error('wrong_password_or_not_activated');
					}
				}
				catch (\Exception $e)
				{
					throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.login_failed', NULL, Module::$name), NULL, $e);
				}

				if ($retval)
				{
					if ($this->request->query(self::PARAM_RETURN_URL))
					{
						//try {
							$this->redirect($this->request->query(self::PARAM_RETURN_URL));
						//}catch(\Exception $e){ var_dump($e); exit; }
					}
					else
					{
						return $this->redirect(Route::get('default')->uri(array(
							'action' => 'index',
							'controller' => 'index'
						)));
					}
				}
			}
			else
			{
				$this->_add_flash_message('message.fix_form_errors', Flasher::MESSAGE_WARNING);
			}
		}
		self::$template->content = View::factory('auth/login', array(
			'form' => $form,
			'form_title' => $title,
			'is_layout' => $is_layout
		), Module::$name);
	}

	public function action_signup()
	{
		$user = AuthHelper::get_current_user();

		if ($user->loaded())
		{
			$this->redirect(Route::get('default')->uri(array(
				'controller' => 'auth',
				'action' => 'logout',
				self::PARAM_RETURN_URL => URL::site($this->request->uri())
			)));
		}

		$request = Request::current();

		$is_layout = is_null($this->request->query('layout')) || $this->request->query('layout') == true;

		$title = Kohana::message('view', 'title.signup', NULL, Module::$name);
		self::$template->title = $title;

		$form_model = Form_FormAbstract::factory('Frontend\Form\User\Signup');

		$form = $form_model->get_form(array('*'));

		$code = $this->request->param('code');

		$inviter_user = null;
		if(strlen($code)) {
			try
			{
				$inviter_user = ORM::factory('Common\Model\User')->find_by_affiliate_code($code);
				/* @var \Common\Model\User $inviter_user */
				if(!$inviter_user->loaded())
				{
					$this->_add_flash_message('message.unable_find_inviter_user', Flasher::MESSAGE_WARNING);
				}
			}
			catch (\Exception $e)
			{
				$this->_add_flash_message('message.unable_find_inviter_user', Flasher::MESSAGE_WARNING);
			}
		}

		if(!is_null($inviter_user) && $inviter_user->loaded()){
			$form->inviter_user_id->val($inviter_user->id);
		}

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($request->post())->validate())
			{
				$form_model->values($form->val());
				if ($form_model->signup())
				{
					$this->_add_flash_message('message.signup_check_your_mail');
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'index'
					)));
				}
				else
				{
					throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.signup_failed', NULL, Module::$name));
				}
			}
			else
			{
				$this->_add_flash_message('message.fix_form_errors', Flasher::MESSAGE_WARNING);
			}
		}
		self::$template->content = View::factory('auth/signup', array(
			'form' => $form,
			'form_title' => $title,
			'is_layout'=> $is_layout,
		), Module::$name);
	}

	public function action_logout()
	{
		if(Ko_Auth::instance()->logout(TRUE)){

			if ($this->request->query(self::PARAM_RETURN_URL))
			{
				$this->redirect($this->request->query(self::PARAM_RETURN_URL));
			}
			else
			{
				$this->redirect(Route::get('default')->uri(array(
					'controller' => 'index'
				)));
			}
		} else {
			$this->_add_flash_message('message.logout_failed', Flasher::MESSAGE_ERROR);
		}
	}

	public function action_mail_confirm()
	{
		$hash = $this->request->query('hash');
		if(!strlen($hash)) {
			throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.required_params_not_given', NULL, Module::$name));
		}

		$user = ORM::factory('Common\Model\User')->where('mail_confirm_hash', '=', $hash)->find();

		if(!$user->loaded()) {
			throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.wrong_link', NULL, Module::$name));
		}
		else
		{
			try
			{
				$user->mail_confirm($hash);
				$this->_add_flash_message('message.signup_finished', Flasher::MESSAGE_SUCCESS);
				// authorize immediatelly this user
				Ko_Auth::instance()->force_login($user);
			}
			catch (\Exception $e)
			{
				throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.signup_could_not_confirm_email', NULL, Module::$name), NULL, $e);
			}
			$this->redirect(Route::get('default')->uri(array(
				'controller' => 'index'
			)));
		}
	}

	public function action_password_reset_ask()
	{
		$request = Request::current();

		$title = Kohana::message('view', 'title.password_reset_ask', NULL, Module::$name);;
		self::$template->title = $title;

		$form_model = Form_FormAbstract::factory('Frontend\Form\User\PasswordResetAsk');
		$form = $form_model->get_form(array('*'));

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($request->post())->validate())
			{
				$user = ORM::factory('Common\Model\User')->where('email', '=', $form->val()['email'])->find();

				if($user && $user->loaded()) {
					try
					{
						$user->password_reset_ask();
						$this->_add_flash_message('message.password_reset_ask_check_your_mail');
					}
					catch (\Exception $e)
					{
						throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.password_reset_ask_failed', NULL, Module::$name), NULL, $e);
					}

					$this->redirect(Route::get('default')->uri(array(
							'controller' => 'index'
					)));

				} else {
					throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.user_not_found', NULL, Module::$name));
				}
			}
			else
			{
				$this->_add_flash_message('message.fix_form_errors', Flasher::MESSAGE_WARNING);
			}
		}
		self::$template->content = View::factory('auth/password-reset-ask', array(
			'form' => $form,
			'form_title' => $title,
		), Module::$name);

	}

	public function action_password_reset()
	{
		$title = Kohana::message('view', 'title.password_reset', NULL, Module::$name);;

		$hash = $this->request->query('hash');
		if(!strlen($hash)) {
			throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.required_params_not_given', NULL, Module::$name));
		}

		$user = ORM::factory('Common\Model\User')->where('password_reset_hash', '=', $hash)->find();

		if(!$user->loaded()) {
			throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.wrong_link', NULL, Module::$name));
		} else {
			$form_model = Form_FormAbstract::factory('Frontend\Form\User\PasswordReset', $user->id);
			$form = $form_model->get_form(array(
				'*'
			));
			// Sets action, default is Request::current()->detect_uri()

			$form->attr('action', $this->request->url() . '?' . http_build_query($this->request->query()));
			if ($this->request->method() == HTTP_Request::POST)
			{
				if ($form->load($this->request->post())->validate())
				{
					try
					{
						$form_model->values($form->val());
						$form_model->password_reset($hash);
						$this->_add_flash_message('message.password_reset_successfully', Flasher::MESSAGE_SUCCESS);
					}
					catch (\Exception $e)
					{
						throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.password_reset_failed', NULL, Module::$name), NULL, $e);
					}
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'auth',
						'action' => 'login',
					)));
				}
			}

			self::$template->content = View::factory('auth/password-reset', array(
			'form' => $form,
			'form_title' => $title,
		), Module::$name);

		}
	}
}