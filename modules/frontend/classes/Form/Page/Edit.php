<?php

namespace Frontend\Form\Page;


use Common\Model\Page;
use Formo;
use Formo_ORM;
use Frontend\Module;

class Edit extends Page {
	use Formo_ORM {
		get_form as get_form_default;
	}

	public function get_form( array $fields, Formo $form = NULL)
	{
		$this->_formo_alias = 'page-edit';
		return $this->get_form_default(array('title', 'slug', 'text', 'is_hidden'), $form);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/page');

		$form->text->set('driver', 'textarea');
	}
}