<?php

namespace Frontend\Form\Affiliate;

use Kohana;
use Formo;
use Formo_Constructor;
use Frontend\Module;
use ORM;

class Invite implements Formo_Constructor{

	protected $_formo_alias = 'invite';

	public function get_form()
	{
		$form = Formo::form(['alias' => $this->_formo_alias]);

		$this->formo($form);
		return $form;
	}

	public function formo(Formo $form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/form/affiliate/invite');

		$form->add('to', 'input|text');
		$form->to->add_rules(array(
			array('not_empty'),
			array('email'),
			array(array(ORM::factory('Common\Model\User'), 'unique'), array('email', ':value'))
		));
		$form->to->set('error_messages', array('unique' => Kohana::message('view', 'message.user_with_such_email_exists', NULL, Module::$name)));

		$form->add('subject', 'input|text');
		$form->subject->add_rules(array(array('not_empty')));

		$form->add('body', 'textarea');
		$form->body->add_rules(array(array('not_empty')));
	}
}