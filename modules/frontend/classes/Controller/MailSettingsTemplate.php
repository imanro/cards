<?php

namespace Frontend\Controller;

use Request;
use Common\Model\Mail\Settings\Template;
use Acl\Rbac;
use Common\Model\Role;

class MailSettingsTemplate extends FrontendController {
	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'get-ajax',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN, Role::NAME_GUEST)
			),
		);
	}

	public function action_get_ajax()
	{
		$this->_init_json_response();
		$request = Request::current();
		$id = $request->param('id');

		$model = Template::factory($id);

		if ($model->loaded())
		{
			$this->response->body(json_encode(array(
				'data' => array(
					'MailSettingsTemplate' => $model->as_array()
				)
			)));
		}
		else
		{
			throw \HTTP_Exception::factory(404, 'template is not found');
		}
	}
}