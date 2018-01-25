<?php

namespace Frontend\Form\Task\Message\Delivery;

use Frontend\Module;
use Form_FormAbstract;
use Formo;
use DateTime;
use DateInterval;
use Common\Helper\Auth as AuthHelper;

class Filter extends Form_FormAbstract {

	protected $_formo_alias = 'filter';

	public function formo(Formo $form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/task-message-delivery');

		$form->add('from', 'input|text');

		$month_ago = new DateTime();
		$month_ago->sub(new DateInterval('P1D'));
		$form->from->val($month_ago->format('Y-m-d'));

		$form->add('to', 'input|text');
		// default value to: today
		$form->to->val(date('Y-m-d'));

		// add user name && user id filter
		$form->add('user_name', 'input|text');
		$form->add('user_id', 'input|hidden');

		$user = AuthHelper::get_current_user();
		$form->user_name->val($user->email);
		$form->user_id->val($user->id);
	}
}