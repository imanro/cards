<?php

namespace Common\Model\Mail;

use ORM;
use Common\Model\User;
use Kohana;
use Auth;
use Email;
use Acl\Dac\ResourceInterface;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $subject
 * @property string $body
 * @property string $create_time
 * @property string $user_id
 * @property bool $system
 *
 * @property Common\Model\User $user
 */
class Template extends ORM implements ResourceInterface {

	// system templates
	const CODE_USER_TEMPLATE_1 = 'user_template_1';

	const CODE_USER_TEMPLATE_2 = 'user_template_2';

	const CODE_SIGNUP_EMAIL_CONFIRM = 'signup_email_confirm';

	const CODE_PASSWORD_RESET_ASK = 'password_reset_ask';

	const CODE_AFFILIATE_INVITE = 'affiliate_invite';

	const CODE_MAILING_UPCOMING_MESSAGES = 'mailing_upcoming_messages';

	const CODE_ALERT_BALANCE = 'alert_balance';

	// original variables, allowed in email
	protected $_variables = array('my_first_name', 'my_last_name', 'recipient_name', 'second_recipient_name');

	protected $_variables_data = array();

	const PLACEHOLDER_DELIMITER_LEFT = '%';

	const PLACEHOLDER_DELIMITER_RIGHT = '%';

	protected $_table_name = 'mail_template';

	protected $_table_columns = array(
		'id' => array(
			'type' => self::DATA_TYPE_INT,
			'data_type' => self::DATA_TYPE_INT,
		),
		'name' => null,
		'code' => array(
			'type' => self::DATA_TYPE_STRING,
			'is_nullable' => TRUE,
		),
		'subject' => null,
		'body' => null,
		'create_time' => null,
		'user_id' => array(
			'type' => self::DATA_TYPE_INT,
			'data_type' => self::DATA_TYPE_INT,
		),
		'system' => array(
			'type' => self::DATA_TYPE_BOOLEAN,
		),
	);

	// Relationships
	protected $_belongs_to = array(
		'user' => array('model' => 'User'),
	);

	/**
	 * @see Acl\Dac\ResourceInterface
	 */
	public function get_owner_id()
	{
		return $this->user_id;
	}

	public function rules()
	{
		return array(
			'name' => array(
				array('not_empty'),
				array('max_length', array(':value', '255'))
			),
			'subject' => array(
				array('not_empty'),
				array('max_length', array(':value', '255'))
			),
			'body' => array(
				array('not_empty'),
			)
		);
	}

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/mail-template');
	}

	public static function create_default_templates(User $user)
	{
		$template1 = ORM::factory(__CLASS__);
		/* @var \Common\Model\Mail\Template $template1 */
		$template1->copy_from(ORM::factory(__CLASS__)->where('code', '=', self::CODE_USER_TEMPLATE_1)->find());
		$template1->user_id = $user->id;

		$template2 = ORM::factory(__CLASS__);
		/* @var \Common\Model\Mail\Template $template2 */
		$template2->copy_from(ORM::factory(__CLASS__)->where('code', '=', self::CODE_USER_TEMPLATE_2)->find());
		$template2->user_id = $user->id;

		return array($template1, $template2);
	}

	public static function create_template_signup_email_confirm()
	{
		$template = ORM::factory(__CLASS__)->where('code', '=', self::CODE_SIGNUP_EMAIL_CONFIRM)->find();
		$template->variables(array('link', 'site_name'));

		return $template;
	}

	public static function create_template_password_reset_ask()
	{
		$template = ORM::factory(__CLASS__)->where('code', '=', self::CODE_PASSWORD_RESET_ASK)->find();
		$template->variables(array('link', 'site_name'));

		return $template;
	}

	public static function create_template_affiliate_invite()
	{
		$template = ORM::factory(__CLASS__)->where('code', '=', self::CODE_AFFILIATE_INVITE)->find();
		$template->variables(array('link', 'my_first_name', 'my_email'));

		return $template;
	}

	public static function create_template_mailing_upcoming_messages()
	{
		$template = ORM::factory(__CLASS__)->where('code', '=', self::CODE_MAILING_UPCOMING_MESSAGES)->find();
		$template->variables(array('my_first_name', 'site_name', 'schedule'));

		return $template;
	}

	public static function create_template_alert_balance()
	{
		$template = ORM::factory(__CLASS__)->where('code', '=', self::CODE_ALERT_BALANCE)->find();
		$template->variables(array('amount', 'link', 'site_name', 'affiliate_site_section_link'));

		return $template;
	}

	public function variables($variables = NULL)
	{
		if (!is_null($variables))
		{
			// setter
			$this->_variables = $variables;
			return $this;
		}
		else
		{
			// getter
			return $this->_variables;
		}
	}

	public function variables_data($variables_data = NULL)
	{
		if (!is_null($variables_data))
		{
			// setter
			$this->_variables_data = $variables_data;
			return $this;
		}
		else
		{
			// getter
			return $this->_variables_data;
		}
	}

	public function bind($key, $value)
	{
		$this->_variables_data[$key] = $value;
	}


	public function get_variables_map($use_untranslated = TRUE)
	{
		$variables = $this->variables();

		$variables_map = array();

		foreach($variables as $name)
		{
			$variables_map[$name] = array();

			if($use_untranslated){
				$variables_map[$name] []= $name;
			}

			if($s = Kohana::message('mail', 'variables.' . $name)) {
				$variables_map[$name] []= $s;
			}
		}

		array_walk($variables_map, function(&$row){
			foreach($row as $key => $value){
				$row[$key] = self::PLACEHOLDER_DELIMITER_LEFT . $value . self::PLACEHOLDER_DELIMITER_RIGHT;
			}
		});

		return $variables_map;
	}

	public function process()
	{
		$data = $this->variables_data();
		$map = $this->get_variables_map();

		$this->subject = $this->parse_variables($this->subject, $map, $data);
		$this->body = $this->parse_variables($this->body, $map, $data);
	}

	public static function parse_variables($string, $map, $data)
	{
		foreach ($map as $variable => $row)
		{
			if (!empty($data[$variable]))
			{
				foreach ($row as $placeholder)
				{
					$re = '/' . preg_quote($placeholder) . '/ui';
					$string = preg_replace($re, $data[$variable], $string);
				}
			}
		}

		$string = preg_replace('/' . self::PLACEHOLDER_DELIMITER_LEFT . '.+?' . self::PLACEHOLDER_DELIMITER_RIGHT . '/', '', $string);
		return $string;
	}

	public function apply_to(Email $mail, $mime_type = NULL)
	{
		$mail->subject($this->subject);

		// autodetect mime
		if (is_null($mime_type))
		{
			if (preg_match('#<.+?>#', $this->body))
			{
				$mime_type = 'text/html';
			}
			else
			{
				$mime_type = 'text/plain';
			}
		}

		$mail->message($this->body, $mime_type);

		return $mail;
	}


	/* Conditions */
	public function where_current_user()
	{
		$user = Auth::instance()->get_user();
		if (!$user || !$user->loaded())
		{
			throw new \Kohana_Exception(vsprintf('Unable to use %s without of authorization', array(
				__CLASS__
			)));
		}
		$this->where('user_id', '=', $user->id);
		return $this;
	}

	public function copy_from(Template $template)
	{
		$properties = array('name', 'subject', 'body');

		foreach($properties as $key) {
			$this->$key = $template->$key;
		}

		return $this;
	}

	protected function _before_save()
	{
		if(!$this->loaded() && is_null($this->user_id)) {
			$user = Auth::instance()->get_user();
			if(!$user || !$user->loaded()){
				throw new \Kohana_Exception(vsprintf('Unable to use %s without of authorization', array(__CLASS__)));
			}
			$this->user_id = $user->id;
		}
	}
}