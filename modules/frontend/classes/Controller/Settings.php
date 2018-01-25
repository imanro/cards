<?php

namespace Frontend\Controller;

use Kohana;
use Frontend\Module;
use Model;
use Common\Model\Role;
use Acl\Rbac;
use View;

class Settings extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'edit-ajax',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_SUPERADMIN)
			),

		);
	}

	public function action_index()
	{
		$title = Kohana::message('view', 'title.settings', NULL, Module::$name);
		$model = Model::factory('Common\Model\Settings');

		/* @var $model \Common\Model\Settings */
		$model->load();

		self::$template->content = View::factory('settings/index', array(
			'controller' => $this,
			'model' => $model,
			'title' => $title,
		), Module::$name);

		// set title _after_ sub-request
		self::$template->title = $title;
	}

	public function action_edit_ajax()
	{
		$this->_init_json_response();

		$model = Model::factory('Common\Model\Settings');
		/* @var $model \Common\Model\Settings */
		$key = $this->request->param('id');
		$value = $this->request->post('value');

		// handling html in settings
		$map = array(
			'&amp;' => "&",
			"&lt;" => "<",
			"&gt;" => ">",
			'&quot;' => '"',
			'&#39;' => "'",
			'&#x2F;' => "/"
		);

		$value = strtr($value, $map);

		$model->load();
		$model->config($key, $value);

		try
		{
			$model->save();
			$this->response->body(json_encode(array(
				'data' => array(
					'Settings' => $model->as_array()
				)
			)));
		}
		catch (\Exception $e)
		{
			throw \HTTP_Exception::factory(500, 'saving failed: ' . $e->getMessage());
		}

		$model->save();
	}
}
