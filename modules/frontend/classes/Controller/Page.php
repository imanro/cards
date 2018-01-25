<?php

namespace Frontend\Controller;

use Frontend\Module;
use Kohana;
use ORM;
use View;
use Form_FormAbstract;
use HTTP_Request;
use Route;
use Flasher;
use Common\Model\Role;
use Acl\Rbac;
use Request;

class Page extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'route',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'view',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'menu-list',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'edit',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			)
		);
	}

	/**
	 * Routing action to allow short page names
	 */
	public function action_route()
	{
		// TODO: rewrite this using redirect
		$slug = $this->request->param('slug');
		switch($slug){
			case('edit'):
				return $this->execute_action('edit');
				break;
			case('menu-list'):
				return $this->execute_action('menu-list');
				break;
			default:
				return $this->execute_action('view');
				break;
		}
	}

	public function action_index()
	{
		$title = Kohana::message('view', 'title.pages', NULL, Module::$name);

		// display all for this user
		$model = ORM::factory('Common\Model\Page');
		/* @var $model \Common\Model\Page */

		self::$template->content = View::factory('page/index', array(
			'controller' => $this,
			'model' => $model,
			'title' => $title,
		), Module::$name);

		// set title _after_ sub-request
		self::$template->title = $title;
	}

	public function action_edit()
	{
		$id = $this->request->param('id');

		$title = !is_null($id) ? Kohana::message('view', 'title.edit_page', NULL, Module::$name) : Kohana::message('view', 'title.add_page', NULL, Module::$name);

		self::$template->title = $title;

		$user = $this->_get_current_user();
		$form_model = Form_FormAbstract::factory('Frontend\Form\Page\Edit', $id);

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
						'controller' => 'page',
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

		self::$template->content = View::factory('page/edit', array(
			'form' => $form,
			'model' => $form_model,
			'form_title' => $title,
			'user' => $user,
		), Module::$name);
	}

	public function action_view()
	{
		// depend of used route
		$slug = $this->request->param('slug')? $this->request->param('slug') : $this->request->param('id');

		$model = ORM::factory('Common\Model\Page')->where('slug', '=', $slug)->find();

		if(!$model->loaded()){
			throw \HTTP_Exception::factory(404);
		} else {
			self::$template->title = $model->title;
			self::$template->content = View::factory('page/view', array(
				'model' => $model
			), Module::$name);
		}
	}

	public function action_menu_list()
	{
		$active_slug = $this->request->param('id')? $this->request->param('id') : $this->request->param('slug');
		$models = ORM::factory('Common\Model\Page')->where('is_hidden', '=', '0')->find_all();
		self::$template->content = View::factory('page/menu-list', array(
			'models' => $models,
			'active_slug' => $active_slug,
		), Module::$name);
	}
}