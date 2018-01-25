<?php

namespace Frontend\Controller;

use Frontend\Module;
use Frontend\Controller\FrontendController;
use Request;
use Kohana;
use Auth;
use Form_FormAbstract;
use HTTP_Request;
use ORM;
use Route;
use View;
use Flasher;
use Acl\Acl;
use Acl\Dac;
use Common\Model\Role;
use Acl\Rbac;

class MailTemplate extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'add',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'edit',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'delete',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'get-ajax',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
		);
	}

	public function action_index()
	{
		$title = Kohana::message('view', 'title.mail_templates', NULL, Module::$name);

		// display all for this user
		$model = ORM::factory('Common\Model\Mail\Template');

		/* @var $model Common\Model\Mail\Template */

		$user = Auth::instance()->get_user();
		if (!$user || !$user->loaded())
		{
			throw new \Kohana_Exception(vsprintf('Unable to use %s without of authorization', array(
				__CLASS__
			)));
		}
		$model->where('user_id', '=', $user->id);

		self::$template->title = $title;

		self::$template->content = View::factory('mail-template/index', array(
			'model' => $model,
			'title' => $title,
			'controller' => $this,
		), Module::$name);
	}

	public function action_add()
	{
		return $this->action_edit();
	}

	public function action_edit()
	{
		$request = Request::current();
		$id = $request->param('id');

		$form_model = Form_FormAbstract::factory('Frontend\Form\Mail\Template\Edit', $id);

		$title = $form_model->loaded()? Kohana::message('view', 'title.edit_mail_template', NULL, Module::$name) : Kohana::message('view', 'title.add_mail_template', NULL, Module::$name);
		self::$template->title = $title;

		$user = $this->_get_current_user();
		if(!Acl::factory('Dac')->has_access(Dac::PERMISSION_WRITE, $user, $form_model)){
			throw new \HTTP_Exception_403(Kohana::message('view', 'message.model_access_denied', 'access denied', Module::$name));
		}

		$form = $form_model->get_form(array('*'));

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($request->post())->validate())
			{
				$form_model->values($form->val());
				if ($form_model->save())
				{
					$this->_add_flash_message('message.saved_successfully');
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'mail-template',
						'action' => 'index'
					)));
				}
				else
				{
					$this->_add_flash_message('message.saving_failed', Flasher::MESSAGE_ERROR);
				}
			}
			else
			{
				$this->_add_flash_message('message.fix_form_errors', Flasher::MESSAGE_WARNING);
			}
		}

		self::$template->content = View::factory('mail-template/edit', array(
			'form' => $form,
			'model' => $form_model,
			'form_title' => $title,
		), Module::$name);
	}

	public function action_delete()
	{
		$id = $this->request->param('id');
		$user = $this->_get_current_user();

 		$model = ORM::factory('Common\Model\Mail\Template', $id);
 		/* @var $model \Common\Model\Mail\Template */

		if(!$model->loaded()) {
			throw new \HTTP_Exception_404(Kohana::message('view', 'message.model_not_found', 'model not found', Module::$name));
		}
		else if (!Acl::factory('Dac')->has_access(Dac::PERMISSION_WRITE, $user, $model))
		{
			throw new \HTTP_Exception_403(Kohana::message('view', 'message.model_access_denied', 'access denied', Module::$name));
		}
		else if($model->system )
		{
			throw new \HTTP_Exception_500(Kohana::message('view', 'message.unable_delete_system_item', 'system item', Module::$name));
		}
		else
		{
			if ($model->delete())
			{
				$this->_add_flash_message('message.deleting_success', Flasher::MESSAGE_SUCCESS);
			}
			else
			{
				$this->_add_flash_message('message.deleting_failed', Flasher::MESSAGE_ERROR);
			}
			$this->redirect(Route::get('default')->uri(array(
				'controller' => 'mail-template',
				'action' => 'index'
			)));
		}
	}

	public function action_get_ajax()
	{
		$this->_init_json_response();

		$user = Auth::instance()->get_user();
		if (!$user || !$user->loaded())
		{
			throw \HTTP_Exception::factory(403, vsprintf('Unable to use %s without of authorization', array(
				__CLASS__
			)));
		}

		$request = Request::current();
		$id = $request->param('id');

		$model = ORM::factory('Common\Model\Mail\Template');
		/* @var $model Common\Model\Mail\Template */

		$model->where('id', '=', $id);
		$model->where('user_id', '=', $user->id);
		$model = $model->find();

		if($model->loaded()) {
			$this->response->body(json_encode(array('data' => array('MailTemplate' => $model->as_array()))));
		} else {
			throw \HTTP_Exception::factory(404, 'template is not found');
		}
	}
}