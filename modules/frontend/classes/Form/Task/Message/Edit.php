<?php

namespace Frontend\Form\Task\Message;


use Common\Model\Task\Message;
use Formo;
use Formo_ORM;
use Frontend\Module;

class Edit extends Message {
	use Formo_ORM {
		get_form as get_form_default;
	}

	public function get_form( array $fields, Formo $form = NULL)
	{
		$this->_formo_alias = 'task-message-edit';
		return $this->get_form_default(array('recipient_name', 'recipient_email', 'second_recipient_name', 'mail_subject', 'mail_body', 'exec_date'), $form);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/task-message');

		$form->mail_body->set('driver', 'textarea');
	}
}