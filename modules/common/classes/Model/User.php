<?php
namespace Common\Model;

use Kohana;
use ORM;
use Auth;
use Database_Expression;
use Common\Module;
use Acl\Dac\SubjectInterface as Dac_SubjectInterface;
use Acl\Rbac\SubjectInterface as Rbac_SubjectInterface;
use Common\Messenger;
use Database;
use Common\Model\Role;
use Arr;
use Common\User\Environment;
use Common\Model\User\Balance;
use Database_Result;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $logins
 * @property string $last_login
 *
 * @property string $first_name
 * @property string $last_name
 *
 * @property string $mail_confirm_hash
 * @property string $password_reset_hash
 *
 * @property int $timezone_id
 *
 * @property string $mail_smtp_host
 * @property string $mail_smtp_port
 * @property bool $mail_smtp_start_tls
 * @property bool $mail_smtp_tls_ssl
 * @property string $mail_smtp_user
 * @property string $mail_smtp_password
 *
 * @property string $create_time
 *
 * @property string $affiliate_code
 * @property int $inviter_user_id
 *
 * @property array $user_tokens
 * @property array $roles
 *
 * @property User $inviter
 * @property \Common\Model\Timezone $timezone
 * @property \Common\Model\Task\Message[] $messages
 */
class User extends ORM implements \Auth_ORM_UserInterface, Dac_SubjectInterface, Rbac_SubjectInterface {

	public $password_confirm;

	protected $_table_name = 'user';

	const ID_UNDEFINED = -1;

	const ID_ROOT = 2;

	protected $_table_columns = array(
		'id' => null,
		'email' => null,
		'password' => null,
		'first_name' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'last_name' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'logins' => null,
		'last_login' => null,

		// For not required values - Formo requires this column description
		'mail_smtp_host' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'mail_smtp_user' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'mail_smtp_port' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'mail_smtp_password' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'mail_smtp_start_tls' => array(
			'data_type' => 'bool',
			'is_nullable' => TRUE
		),
		'mail_smtp_tls_ssl' => array(
			'data_type' => 'bool',
			'is_nullable' => TRUE
		),

		'password_reset_hash' => null,
		'mail_confirm_hash' => null,
		'create_time' => null,
		'affiliate_code' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),
		'inviter_user_id' => array(
			'data_type' => 'integer',
			'is_nullable' => TRUE
		),
		'timezone_id' => null,
	);

	protected $_has_many = array(
		'user_tokens' => array('model' => 'Common\Model\User\Token'),
		'roles'       => array('model' => 'Role', 'through' => 'role_user'),
		'messages' => array('model' => 'Common\Model\Task\Message'),
	);

	protected $_has_one = array(
		'balance'     => array('model' => 'Common\Model\User\Balance'),
	);

	protected $_belongs_to = array(
		'inviter' => array('model' => 'User', 'foreign_key' => 'inviter_user_id'),
		'timezone' => array('model' => 'Timezone', /* 'formo' => TRUE */),
	);

	/**
	 * @see \Acl\Dac\SubjectInterface::get_id()
	 */
	public function get_id()
	{
		return $this->id;
	}

	public function get_roles()
	{
		return $this->roles->find_all();
	}

	public function has_role($id)
	{
		return self::has('roles', $id);
	}


	/**
	 * @see Model_Auth_User::rules()
	 */
	public function rules()
	{
		return array(
			'email' => Arr::merge($this->_base_rules_email(), array(
				array(array($this, 'unique'), array('email', ':value'))
			)),
			'password' => $this->_base_rules_password(),
			'first_name' => array(
				array('not_empty'),
				array('max_length', array(':value', 255)),
			),
			'last_name' => array(
				array('not_empty'),
				array('max_length', array(':value', 255)),
			),
			'mail_smtp_host' => array(
				array('max_length', array(':value', 255)),
			),
			'mail_smtp_user' => array(
				array('max_length', array(':value', 150)),
			),
			'mail_smtp_password' => array(
				array('max_length', array(':value', 64)),
			),
			'timezone_id' => array(
				array(array(ORM::factory('Timezone'), 'exists'), array('id', ':value'))
			),
		);
	}

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/user');
	}

	protected function _base_rules_email()
	{
		return array(
				array('email'),
		);
	}

	protected function _base_rules_password()
	{
		return array(
			array('min_length', array(':value', 6)),
		);
	}

	public function init()
	{
		if(!$this->loaded()){
			$this->timezone_id = Timezone::ID_MOSCOW;
		}
	}
	public function filters()
	{
		return array(
			'password' => array(
				array(array(Auth::instance(), 'hash'))
			)
		);
	}

	public function get($column)
	{
		if($column == 'affiliate_code') {
			$value = (in_array($column, $this->_serialize_columns))
				? $this->_unserialize_value($this->_object[$column])
				: $this->_object[$column];
			// generation + saving of affiliate code
			if(!$value){
				$this->affiliate_code = $this->gen_affiliate_code();
				$this->skip_validation();
				$this->save();
			}

			return parent::get($column);

		} else {
			return parent::get($column);
		}
	}
	public function set($column, $value)
	{
		if($column == 'password' && empty($value)){
			// not overwrite password when user wa'nt to change it
			return $this;
		} else if($column == 'mail_smtp_password'){
			if(!empty($this->mail_smtp_host)){
				if(!empty($value)){
					return parent::set($column, $value);
				} else {
					return $this;
				}
			} else {
				return parent::set($column, '');
			}
		} else {
			return parent::set($column, $value);
		}
	}

	public function complete_login()
	{
		if ($this->_loaded)
		{
			// Update the number of logins
			$this->logins = new Database_Expression('`logins` + 1');

			// Set the last login date
			$this->last_login = new Database_Expression('NOW()');

			$this->skip_validation(TRUE);

			// Save the user
			$this->update();
		}
	}

	/**
	 * Required by Auth module
	 * @param unknown $value
	 * @return string
	 */
	public function unique_key()
	{
		return 'email';
	}

	/*
	 * Business logic
	 */
	public function signup()
	{
			/* $validation = new \Validation(array());
		 * $validation->rule('password', 'not_empty');
		 * $validation->error('password', 'laga: :aaa', array(':aaa' => 'bbb'));
		 * throw new \ORM_Validation_Exception('pipip', $validation, 'Fffff'); */
			// generating password-reset-hash
		try
		{
			Database::instance()->begin();

			$this->mail_confirm_hash = $this->gen_mail_confirm_hash();
			if (Messenger::notify_signup_email_confirm($this))
			{
				$this->save();
				Database::instance()->commit();
			} else {
				Database::instance()->rollback();
				return false;
			}
		}
		catch (\Exception $e)
		{
			Database::instance()->rollback();
			throw $e;
		}
		return true;
	}

	public function complete_signup()
	{
		// add user to login group
		$this->skip_validation();
		$this->add( 'roles', ORM::factory( 'Common\Model\Role' )->where( 'name', '=', Role::NAME_LOGIN )->find() );
		$this->save();

		// create user environment
		Environment::init_user_environment($this);
	}

	public function mail_confirm($hash)
	{
		if($this->mail_confirm_hash === $hash) {
			// cleanup hash
			$this->skip_validation();
			$this->mail_confirm_hash = '';
			$this->save();

			$this->complete_signup();
			return true;

		} else {
			throw new \Kohana_Exception('Wrong hash given');
		}
	}

	public function password_reset_ask()
	{
		$this->skip_validation();
		$this->password_reset_hash = $this->gen_password_reset_hash();
		$this->save();
		Messenger::notify_password_reset_ask($this);
		return true;
	}

	public function password_reset($hash)
	{
		if($this->password_reset_hash === $hash) {
			// cleanup hash
			$this->password_reset_hash = '';
			// save with _new_ password received from form
			$this->save();
			return true;

		} else {
			throw new \Kohana_Exception('Wrong hash given');
		}
	}


	public function user_email_exists($value)
	{
		return $this->exists('email', $value);
	}

	public function format_name()
	{
		return implode(' ', array($this->first_name, $this->last_name));
	}

	public function gen_password_reset_hash()
	{
		$counter = 0;
		$search_model = ORM::factory('Common\Model\User');

		do
		{
			$hash = hash('sha256', uniqid('prh', TRUE));
		}
		while ($search_model->where('password_reset_hash', '=', $hash)->find()->loaded() && $counter++ < 10);
		if ($counter == 10)
		{
			throw new \Kohana_Exception('Unable generate unique password reset hash');
		}

		return $hash;
	}

	public function gen_mail_confirm_hash()
	{
		$counter = 0;
		$search_model = ORM::factory('Common\Model\User');

		do
		{
			$hash = hash('sha256', uniqid('mch', TRUE));
		}
		while ($search_model->where('mail_confirm_hash', '=', $hash)->find()->loaded() && $counter++ < 10);
		if ($counter == 10)
		{
			throw new \Kohana_Exception('Unable generate unique mail confirm hash');
		}

		return $hash;
	}

	/**
	 * @return \Common\Model\User\Balance
	 */
	public function get_balance()
	{
		return Balance::get_for_user($this);
	}

	public function gen_affiliate_code()
	{
		if(!$this->loaded()){
			throw new \Kohana_Exception('Model must be loaded to generate affiliate code');
		}

		$search_model = ORM::factory('Common\Model\User');
		$counter = 0;

		do
		{
			$code = 'p' . $this->id;
		}
		while ($search_model->where('affiliate_code', '=', $code)->find()->loaded() && $counter++ < 10);
		if ($counter == 10)
		{
			throw new \Kohana_Exception('Unable generate unique affiliate code for user');
		}

		return $code;
	}

	public static function find_by_affiliate_code($code)
	{
		$re = '/p([0-9])+/';
		if(preg_match($re, $code, $matches)){
			$id = $matches[1];
		} else {
			throw new \Exception('Unable to find user by this code');
		}

		return ORM::factory(__CLASS__, $id);
	}

	public static function pull_into_transactions(Database_Result $users, $transactions)
	{
		$assoc = $users->as_array('id');

		foreach($transactions as $transaction){
			/* @var Common\Model\Task\Message */
			if(isset($assoc[$transaction->resource])) {
				$transaction->invited_user($assoc[$transaction->resource]);
			}
		}
	}
}