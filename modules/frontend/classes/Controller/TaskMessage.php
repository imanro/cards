<?php

namespace Frontend\Controller;

use Frontend\Module;
use Frontend\Controller\FrontendController;
use Request;
use Kohana;
use Form_FormAbstract;
use HTTP_Request;
use ORM;
use Route;
use View;
use Flasher;
use DataGrid;
use Acl\Acl;
use Acl\Dac;
use Acl\Rbac;
use Common\Model\Role;
use Session;

class TaskMessage extends FrontendController {

	const SESSION_VAR_ADDED_IDS = 'task_message_added_ids';

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
				Rbac::MAP_VAR_RESOURCES => 'csv-import',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'delete',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'edit-ajax',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			)

		);
	}
	public function action_index()
	{
		if ($this->request->method() == HTTP_Request::POST){
			// processing add
			$this->action_edit();
		}

		$title = Kohana::message('view', 'title.dates', NULL, Module::$name);

		$user = $this->_get_current_user();

		// display all for this user
		$model = ORM::factory('Common\Model\Task\Message');
		/* @var $model \Common\Model\Task\Message */

		$model->where('user_id', '=', $user->id)->
		where('hidden', '=', '0')->
		order_by('exec_date', 'ASC');

		// special optimization to not get deliveries in cycle
		$callback_delivery = function(DataGrid $datagrid)
		{
			$model_delivery = ORM::factory('Common\Model\Task\Message\Delivery');
			/* @var $model_delivery \Common\Model\Task\Message\Delivery */
			$result = $datagrid->data();
			$ids = $model_delivery->extract_column_values($result, 'id');

			if (count($ids))
			{
				$model_delivery->scope_last_for_messages($result)
				// CHECKME: is following needed? (see code of scope_last_for_messages())
				->where('delivery.task_message_id', 'in', $ids);
			}
			$result = $model_delivery->find_all();
			$model_delivery->pull_into_tasks($result, $datagrid->data());
		};

		$template_save = self::$template;

		$add_form = Request::factory(Route::get('default')->uri(array(
				'controller' => 'task-message',
				'action' => 'add',
				'layout' => 0
			)))->execute();

		$csv_import_form = Request::factory(Route::get('default')->uri(array(
				'controller' => 'task-message',
				'action' => 'csv-import',
				'layout' => 0,
			)))->execute();

		$added_ids = Session::instance()->get_once(self::SESSION_VAR_ADDED_IDS);

		self::$template = $template_save;

		self::$template->content = View::factory('task-message/index', array(
			'controller' => $this,
			'model' => $model,
			'title' => $title,
			'add_form' => $add_form,
			'added_ids' => $added_ids,
			'csv_import_form' => $csv_import_form,
			'callback_delivery' => $callback_delivery,
		), Module::$name);

		// set title _after_ sub-request
		self::$template->title = $title;
	}

	public function action_add()
	{
		return $this->action_edit();
	}

	public function action_edit()
	{
		$id = $this->request->param('id');

		$layout = is_null($this->request->query('layout')) || $this->request->query('layout') == true;

		$form_model = Form_FormAbstract::factory('Frontend\Form\Task\Message\Edit', $id);

		if($layout)
		{
			$title = $form_model->loaded() ? Kohana::message('view', 'title.edit_date', NULL, Module::$name) : Kohana::message('view', 'title.add_date', NULL, Module::$name);
		}
		else
		{
			$title = '';
		}

		self::$template->title = $title;

		$user = $this->_get_current_user();

		if(!Acl::factory('Dac')->has_access(Dac::PERMISSION_WRITE, $user, $form_model)){
			throw new \HTTP_Exception_403(Kohana::message('view', 'message.model_access_denied', 'access denied', Module::$name));
		}

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
						'controller' => 'task-message',
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

		$mail_template_model = ORM::factory('Common\Model\Mail\Template');

		self::$template->content = View::factory('task-message/edit', array(
			'form' => $form,
			'model' => $form_model,
			'form_title' => $title,
			'mail_template_model' => $mail_template_model,
			'user' => $user,
			'layout' => $layout,
		), Module::$name);
	}

	public function action_csv_import()
	{
		$form_model = Form_FormAbstract::factory('Frontend\Form\Task\Message\CsvImport');
		$form = $form_model->get_form();

		$title = Kohana::message('view', 'title.csv_import', NULL, Module::$name);

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($this->request->post())->validate())
			{
				$model = ORM::factory('Common\Model\Task\Message');
				/* @var $model \Common\Model\Task\Message */

				try {
					$added_ids = $model->import_csv($_FILES[$form->get('alias')]['tmp_name'][$form->csv_file->get('alias')]);
				} catch(\Exception $e) {
					throw \HTTP_Exception::factory(500, Kohana::message('view', 'message.csv_import_failed', NULL, Module::$name), NULL, $e);
				}

				if(count($added_ids)) {
					$this->_add_flash_message('message.csv_file_imported_successfully');
				}

				$session = Session::instance();
				$session->set(self::SESSION_VAR_ADDED_IDS, $added_ids);

				$this->redirect(Route::get('default')->uri(array(
					'controller' => 'task-message',
					'action' => 'index'
				)));
			}
		}

		self::$template->content = View::factory('task-message/csv-import', array(
			'form' => $form,
			'model' => $form_model,
			'form_title' => $title,
		), Module::$name);
	}

	public function action_delete()
	{
		$id = $this->request->param('id');
		$user = $this->_get_current_user();

		$model = ORM::factory('Common\Model\Task\Message', $id);

		if(!$model->loaded()) {
			throw new \HTTP_Exception_404(Kohana::message('view', 'message.model_not_found', 'model not found', Module::$name));
		} else if(!Acl::factory('Dac')->has_access(Dac::PERMISSION_WRITE, $user, $model)) {
			throw new \HTTP_Exception_403(Kohana::message('view', 'message.model_access_denied', 'access denied', Module::$name));
		} else {
			if( $model->delete() ) {
				$this->_add_flash_message('message.deleting_success', Flasher::MESSAGE_SUCCESS);
			} else {
				$this->_add_flash_message('message.deleting_failed', Flasher::MESSAGE_ERROR);
			}
			$this->redirect(Route::get('default')->uri(array(
						'controller' => 'task-message',
						'action' => 'index'
					)));

		}
	}

	public function action_edit_ajax()
	{
		$this->_init_json_response();
		$user = $this->_get_current_user();
		$id = $this->request->param('id');

		$model = ORM::factory('Common\Model\Task\Message', $id);
		/* @var $model \Common\Model\Task\Message */

		if (!$model->loaded())
		{
			throw \HTTP_Exception::factory(404, 'model not found');
		}
		else if (!Acl::factory('Dac')->has_access(Dac::PERMISSION_WRITE, $user, $model))
		{
			throw \HTTP_Exception::factory(403, 'access denied');
		}
		else
		{
			$model->values($_POST);

			// use try..catch, save always return ORM obj...
			if ($model->save()){
				$this->response->body(json_encode(array('data' => array('TaskMessage' => $model->as_array()))));
			} else {
				throw \HTTP_Exception::factory(500, 'saving failed');
			}
		}
	}
}