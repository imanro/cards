<?php

namespace Frontend\Form\Mail\Template;


use Common\Model\Mail\Template;
use Formo;
use Formo_ORM;
use Frontend\Module;

class Edit extends Template {
	use Formo_ORM {
		get_form as get_form_default;
	}

	public function get_form( array $fields, Formo $form = NULL)
	{
		$this->_formo_alias = 'mail-template-edit';
		return $this->get_form_default(array('name', 'subject', 'body'), $form);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/mail-template');

		$form->body->set('driver', 'textarea');
	}
}