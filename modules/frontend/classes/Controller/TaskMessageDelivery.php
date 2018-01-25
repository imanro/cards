<?php

namespace Frontend\Controller;

use Frontend\Module;
use Frontend\Controller\FrontendController;
use Common\Helper\Auth as AuthHelper;
use Kohana;
use ORM;
use View;
use Common\Model\Role;
use Acl\Rbac;
use Form_FormAbstract;
use Common\Model\Task\Message\Delivery;

class TaskMessageDelivery extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(
					Role::NAME_LOGIN
				)
			)
		);
	}

	public function action_index()
	{
		$title = Kohana::message('view', 'title.task_message_delivery', NULL, Module::$name);

		// display all for this user
		$model = ORM::factory('Common\Model\Task\Message\Delivery');
		/* @var $model Common\Model\Task\Message */

		$form_model = \Form_FormAbstract::factory('Frontend\Form\Task\Message\Delivery\Filter');
		/* @var $filter_form \Frontend\Form\Task\Message\Delivery\Filter */
		$filter_form = $form_model->get_form();

		$filter_form->load($this->request->query());

		$model->
		with('task_message', 'INNER')->
		order_by('delivery.create_time', 'DESC');

		$model->search($filter_form->val());

		$count_model = clone $model;
		$stat = array(
			'success_deliveries' => $count_model->
				where('state', '=', Delivery::STATE_SUCCESS)->
				count_all(),
		);

		$user = $this->_get_current_user();

		if(!AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN)){
			$model->where('task_message.user_id', '=', $user->id);
		}

		self::$template->content = View::factory('task-message-delivery/index', array(
			'controller' => $this,
			'model' => $model,
			'title' => $title,
			'filter_form' => $filter_form,
			'stat' => $stat,
		), Module::$name);

		// set title _after_ sub-request
		self::$template->title = $title;
	}
}