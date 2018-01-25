<?php

namespace Frontend\Form\User;

use Common\Model\User;
use Formo_ORM;
use Formo;
use Arr;
use Frontend\Module;

class PasswordResetAsk extends User {

	use Formo_ORM {
		get_form as get_form_default;
	}

	/**
	 * Required staff
	 * @see Kohana_ORM::object_name()
	 */
	public function object_name($name = NULL)
	{
		if(!is_null($name)){
			return parent::object_name(get_parent_class());
		} else {
			return parent::object_name();
		}
	}

	public function get_form( array $fields, Formo $form = NULL)
	{
		$this->_formo_alias = 'password-reset-ask';
		return $this->get_form_default(array('email'));
	}

	public function rules()
	{
		return array(
			'email' => Arr::merge($this->_base_rules_email(), array(
				array(array($this, 'user_email_exists'), array(':value'))
			)),
		);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/user');

		/* E-mail */
		$form->email->set('driver', 'input|email');
	}
}
