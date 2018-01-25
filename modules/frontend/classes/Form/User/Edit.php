<?php

namespace Frontend\Form\User;

use Kohana;
use Common\Model\User;
use Formo_ORM;
use Formo;
use ORM;
use Arr;
use Frontend\Module;
use Form_FormAbstract;

class Edit extends User {

	public $mode_add = FALSE;

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
		$this->_formo_alias = 'user-edit';
		return $this->get_form_default(array(
			'email', 'password', 'first_name', 'last_name', 'timezone_id',
			'mail_smtp_host', 'mail_smtp_user', 'mail_smtp_password', 'mail_smtp_port', 'mail_smtp_start_tls', 'mail_smtp_tls_ssl',
			'balance' => array('value')
		), $form);
	}

	protected function _rules_password_confirm()
	{
		return array(
				array('matches', array(':form_val', ':field', 'password'))
		);
	}

	public function rules()
	{
		if ($this->mode_add)
		{
			// array merge puts rules in right order, Arr::merge not
			return array_merge(parent::rules(), array(
				'password' => Arr::merge(array(array('not_empty')), $this->_base_rules_password())
			));
		}
		else
		{
			// array merge puts rules in right order, Arr::merge not
			return array_merge(parent::rules(), array(
				'password' => Arr::merge(array(array('empty_rules')), $this->_base_rules_password())
			));
		}
	}

	public function formo(Formo $form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/model/user');

		/* E-mail */
		$form->email->set('driver', 'input|email');
		$form->email->set('error_messages', array('unique' => Kohana::message('view', 'message.user_with_such_email_exists', NULL, Module::$name)));

		/* Password */
		$form->password->set('driver', 'input|password');

		/* Timezone */
		/* Select for fkeys can be automatically initialized by belongs_to settings 'formo' => TRUE in User model. See Formo:ORM docs. Not used because we need to */
		$form->timezone_id->set('driver', 'select');
		$form->timezone_id->set('opts', ORM::factory('Common\Model\Timezone')->get_form_options_timezone());

		/* Mail Smtp settings */
		$form->mail_smtp_start_tls->set('driver', 'checkbox');
		$form->mail_smtp_tls_ssl->set('driver', 'checkbox');
		$form->mail_smtp_password->set('driver', 'input|password');

		/* Password Confirm */
		$form->add('password_confirm', 'input|password');

		// here to omit validation during User->register_user, and only validate in formo scope
		$password_confirm_rules = array_merge($this->mode_add? array(array('not_empty')): array(), $this->_rules_password_confirm());
		$form->password_confirm->add_rules($password_confirm_rules);
		$form->password_confirm->set('label', $this->label('password_confirm'));

	}

	/**
	 * Special method for relational forms
	 */
	public function formo_balance()
	{
		$model =  Form_FormAbstract::factory('Frontend\Form\User\Balance\Edit')->where('user_id', '=', $this->id)->find();
		if(!$model->loaded()) {
			$model->value = Kohana::$config->load('settings')->get('default_balance');
		}
		return $model;
	}
}
