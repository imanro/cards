<?php

namespace Frontend\Form\User;

use Common\Model\User;
use Formo_ORM;
use Formo;
use Arr;
use Frontend\Module;
use Kohana;
use ORM;

class Signup extends User {

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
		$this->_formo_alias = 'user-signup';
		return $this->get_form_default(array(
			'email', 'password', 'first_name', 'last_name', 'timezone_id',
			'mail_smtp_host', 'mail_smtp_user', 'mail_smtp_password', 'mail_smtp_port', 'mail_smtp_start_tls', 'mail_smtp_tls_ssl',
			'inviter_user_id'
		), $form);
	}

	protected function _rules_password_confirm()
	{
		return array(
			'password_confirm' => array(
				array('not_empty'),
				array('matches', array(':form_val', ':field', 'password'))
			)
		);
	}

	protected function _rules_accept_agreement()
	{
		return array(
			// unique for email is to user with same email not exists in db

			'accept_agreement' => array(
				array('not_empty'),
			)
		);
	}

	public function rules()
	{
		return Arr::merge(parent::rules(), array());
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

		$form->inviter_user_id->set('driver', 'input|hidden');

		/* Timezone */
		/* Select for fkeys can be automatically initialized by belongs_to settings 'formo' => TRUE in User model. See Formo:ORM docs. Not used because we need to */
		$form->timezone_id->set('driver', 'select');
		$form->timezone_id->set('opts', ORM::factory('Common\Model\Timezone')->get_form_options_timezone());

		/* Mail Smtp Host */
		$form->mail_smtp_start_tls->set('driver', 'checkbox');
		$form->mail_smtp_tls_ssl->set('driver', 'checkbox');
		$form->mail_smtp_password->set('driver', 'input|password');

		/* Password Confirm */
		$form->add('password_confirm', 'input|password');
		// here to omit validation during User->register_user, and only validate in formo scope
		$password_confirm_rules = $this->_rules_password_confirm();
		$form->password_confirm->add_rules($password_confirm_rules['password_confirm']);
		$form->password_confirm->set('label', $this->label('password_confirm'));

		/* Accept aggreement */
		$form->add('accept_agreement', 'checkbox');
		$form->accept_agreement->add_rules($this->_rules_accept_agreement()['accept_agreement']);
		$form->accept_agreement->set('error_messages', array('not_empty' => Kohana::message('view', 'message.you_have_to_accept_aggrement', NULL, Module::$name)));
	}
}