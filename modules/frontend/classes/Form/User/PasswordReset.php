<?php

namespace Frontend\Form\User;

use Common\Model\User;
use Formo_ORM;
use Formo;
use Arr;
use Frontend\Module;

class PasswordReset extends User {

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
		$this->_formo_alias = 'password-reset';
		return $this->get_form_default(array('password'));
	}

	protected function _rules_password_confirm()
	{
		return array(
				array('matches', array(':form_val', ':field', 'password'))
		);
	}

	public function rules()
	{
		return array(
			'password' => $this->_base_rules_password(),
		);
	}

	public function formo($form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/user');

		/* Password */
		$form->password->set('driver', 'input|password');

		/* Password Confirm */
		$form->add('password_confirm', 'input|password');
		// here to omit validation during User->register_user, and only validate in formo scope
		$form->password_confirm->add_rules($this->_rules_password_confirm());
		$form->password_confirm->set('label', $this->label('password_confirm'));
	}
}
