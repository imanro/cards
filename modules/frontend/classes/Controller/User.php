<?php

namespace Frontend\Controller;

use Frontend\Module;
use Common\Model\User as UserModel;
use Auth;
use Kohana;
use Form_FormAbstract;
use Route;
use View;
use Flasher;
use HTTP_Request;
use ORM;
use Common\Model\Role;
use Acl\Rbac;
use Common\Model\Task\Message\Delivery;
use Common\Helper\Auth as AuthHelper;

class User extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'add',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'edit',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'self-edit',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'delete',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'search-ajax',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
		);
	}
	public function action_index()
	{
		$title = Kohana::message('view', 'title.users', NULL, Module::$name);

		// count all users
		$stat = array(
			'users' => ORM::factory('Common\Model\User')->count_all(),
			'success_deliveries' => ORM::factory('Common\Model\Task\Message\Delivery')->
				with('task_message')->
				where('state', '=', Delivery::STATE_SUCCESS)->
				where('task_message.user_id', '!=', UserModel::ID_ROOT)->
				count_all(),
		);

		// display all for this user
		$model = ORM::factory('Common\Model\User');
		/* @var $model \Common\Model\User */

		$model->with('balance', 'LEFT');

		$delivery_model = ORM::factory('Common\Model\Task\Message\Delivery');
		/* @var $delivery_model \Common\Model\Task\Message\Delivery */
		$delivery_model->join_deliveries_count_to_user($model, 'state=\'' . Delivery::STATE_SUCCESS . '\'');


		self::$template->content = View::factory('user/index', array(
			'controller' => $this,
			'model' => $model,
			'title' => $title,
			'stat' => $stat,
		), Module::$name);

		// set title _after_ sub-request
		self::$template->title = $title;
	}

	public function action_add()
	{
		return $this->action_edit(TRUE);
	}


	public function action_edit($mode_add = FALSE)
	{
		$id = $this->request->param('id');
		$title = !is_null($id) ? Kohana::message('view', 'title.edit_user', NULL, Module::$name) : Kohana::message('view', 'title.add_user', NULL, Module::$name);

		self::$template->title = $title;

		$form_model = Form_FormAbstract::factory('Frontend\Form\User\Edit', $id, array('mode_add' => $mode_add));
		$form = $form_model->get_form(array('*'));

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($this->request->post())->validate())
			{
				$form_model->values($form->val());

				if ($form_model->save())
				{
					if(AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN)){
						$balance_form_model = Form_FormAbstract::factory('Frontend\Form\User\Balance\Edit');
						$balance_form_model->values($form->val()['balance']);
						$balance_form_model->save_for_user($form_model);
					}

					$this->_add_flash_message('message.saved_successfully');
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'user',
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

		self::$template->content = View::factory('user/edit', array(
			'form' => $form,
			'form_title' => $title,
			'controller' => $this,
		), Module::$name);
	}

	public function action_self_edit()
	{
		$user = Auth::instance()->get_user();
		if(!$user) {
			throw new \Common\Auth\Exception('User is not logged in', \Common\Auth\Exception::CODE_NOT_AUTHORIZED);
		}

		$id = $user->id;

		$title = Kohana::message('view', 'title.edit_self_user', NULL, Module::$name);

		self::$template->title = $title;

		$form_model = Form_FormAbstract::factory('Frontend\Form\User\Edit', $id);

		$form = $form_model->get_form(array('*'));

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($this->request->post())->validate())
			{
				$form_model->values($form->val());
				if ($form_model->save())
				{
					$this->_add_flash_message('message.saved_successfully');
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'index',
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

		self::$template->content = View::factory('user/edit', array(
			'form' => $form,
			'model' => $form_model,
			'form_title' => $title,
			'controller' => $this,
		), Module::$name);
	}

	public function action_delete()
	{
		$id = $this->request->param('id');

		$model = ORM::factory('Common\Model\User', $id);

		if(!$model->loaded()) {
			throw new \HTTP_Exception_404(Kohana::message('view', 'message.model_not_found', 'model not found', Module::$name));
		} elseif($model->id == UserModel::ID_ROOT){
			throw new \HTTP_Exception_500(Kohana::message('view', 'message.unable_delete_root', 'unable delete root', Module::$name));
		} else {
			if( $model->delete() ) {
				$this->_add_flash_message('message.deleting_success', Flasher::MESSAGE_SUCCESS);
			} else {
				$this->_add_flash_message('message.deleting_failed', Flasher::MESSAGE_ERROR);
			}
			$this->redirect(Route::get('default')->uri(array(
						'controller' => 'user',
						'action' => 'index'
					)));
		}
	}

	public function action_search_ajax()
	{
		$this->_init_json_response();

		$user = Auth::instance()->get_user();
		if (!$user || !$user->loaded())
		{
			throw \HTTP_Exception::factory(403, vsprintf('Unable to use %s without of authorization', array(
				__CLASS__
			)));
		}

		$email = $this->request->param('id');

		$model = ORM::factory('Common\Model\User');
		/* @var $model Common\Model\User */

		$model->where('email', 'LIKE', $email . '%');
		$models = $model->find_all();

		$this->response->body(json_encode(array('data' => array('User' => $models->as_array()))));
	}
}
