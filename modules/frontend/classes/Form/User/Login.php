<?php

namespace Frontend\Form\User;

use Common\Model\User;
use Formo_ORM;
use Formo;
use Arr;
use Frontend\Module;

class Login extends User {

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
		$this->_formo_alias = 'user-login';
		return $this->get_form_default(array('email', 'password'));
	}

	public function rules()
	{
		return array(
			// Empty rules is special rule to empty default formo rules
			'email' => Arr::merge($this->_base_rules_email(), array(
					array(array($this, 'user_email_exists'), array(':value'))
				)
			),
			'password' => array(
					array('not_empty')
				)
		);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/user');

		/* E-mail */
		$form->email->set('driver', 'input|email');

		/* Password */
		$form->password->set('driver', 'input|password');

		/* Remember me */
		$form->add('remember_me', 'checkbox');
	}
}
