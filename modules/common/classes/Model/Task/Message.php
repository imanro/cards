<?php

namespace Common\Model\Task;

use Common\Model\TaskInterface;
use Kohana;
use Common\Module;
use ORM;
use Model;
use Email;
use Auth;
use Text;
use Common\Model\Task\Message\Delivery;
use Common\Model\Mail\Template;
use Common\Model\User;
use Common\Mail\Transport;
use Acl\Dac\ResourceInterface;
use DateTime;
use Database_Expression;
use Common\Model\Timezone;
use Common\Model\Role;
use Common\Helper\Auth as AuthHelper;
use CSV;

/**
 * @author manro
 *
 * @property int $id
 * @property string $recipient_name
 * @property string $recipient_email
 * @property string $second_recipient_name
 * @property string $mail_subject
 * @property string $mail_body
 * @property int $user_id
 * @property string $exec_date
 * @property string $create_time
 * @property string $state
 * @property bool $active
 * @property bool $hidden
 * @property bool $free
 * @property bool $repeated
 * @property string $repeat_interval_measure
 * @property int $repeat_interval
 *
 * @property User $user
 */
class Message extends ORM implements TaskInterface, ResourceInterface {

	const CSV_COLUMNS_COUNT = 4;

	protected $_mail;

	protected $_mail_transport;

	protected $_table_name = 'task_message';

	/**
	 * @var Delivery Cached delivery
	 */
	protected $_delivery;

	protected $_table_columns = array(
		'id' => null,
		'recipient_name' => null,
		'recipient_email' => null,
		'second_recipient_name' => array(
			'type' => self::DATA_TYPE_STRING,
			'is_nullable' => TRUE
		),
		'mail_subject' => null,
		'mail_body' => null,
		'user_id' => null,
		'create_time' => null,
		'exec_date' => null,
		'hidden' => array(
			'data_type' => self::DATA_TYPE_BOOLEAN
		),
		'active' => array(
			'data_type' => self::DATA_TYPE_BOOLEAN
		),
		'free' => array(
			'data_type' => self::DATA_TYPE_BOOLEAN
		),
		'repeated' => array(
			'data_type' => self::DATA_TYPE_BOOLEAN
		),
		'repeat_interval_measure' => null,
		'repeat_interval' => array(
			'data_type' => self::DATA_TYPE_INT
		),
	);

	protected $_belongs_to = array(
		'user' => array('model' => 'User'),
	);

	protected $_has_many = array(
		'deliveries' => array(
			'model' => 'Common\Model\Task\Message\Delivery',
			'foreign_key' => 'task_message_id',
		)
	);

	/**
	 * @see \Common\Classes\Acl\Dac\ResourceInterface::get_owner_id()
	 */
	public function get_owner_id()
	{
		return $this->user_id;
	}

	public function init()
	{
		if(!$this->loaded()){
			$this->active = true;
		}
	}

	public function rules()
	{
		return array(
			'recipient_name' => array(
				array('not_empty'),
				array('max_length', array(':value', '255')),
			),
			'recipient_email' => array(
				array('not_empty'),
				array('max_length', array(':value', '255')),
				array('email'),
			),
			'mail_subject' => array(
				array('not_empty'),
				array('max_length', array(':value', '255')),
			),
			'mail_body' => array(
				array('not_empty'),
				array('max_length', array(':value', '4000')),
			),
			// CHECKME
			'user_id' => array(
				array('not_empty'),
			)
		);
	}


	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/task-message');
	}

	public function exec()
	{
		if(!$this->user->has_role(Role::ID_SUPERADMIN)){
			// use balance
			if(!$this->user->get_balance()->check_value()){
				$this->_log_delivery(Delivery::STATE_FAILURE,
					new \Common\Model\Task\Message\Exception(
						Kohana::message('view', 'message.not_enough_balance', 'Not enough value on user\'s balance', Module::$name),
						\Common\Model\User\Balance\Exception::CODE_SMALL_VALUE
					));
				return false;
			}
		}
		// ! no checking for active / repeated, this allows to exec messages by messenger
		// getting mail
		$mail = $this->mail();

		// getting transport (class that contains methods to send email and credentials)
		$transport = $this->mail_transport();

			// save new tasks before executing
		if (!$this->loaded())
		{
			try
			{
				$this->save();
			}
			catch (\Exception $e)
			{
				var_dump($this->validation()->errors());
				throw $e;
			}
		}

		$result = false;
		try
		{
			if($transport->send($mail)){
				$this->_log_delivery(Delivery::STATE_SUCCESS);
				$result = true;
			} else {
				$this->_log_delivery(Delivery::STATE_FAILURE);
				$result = false;
			}
		}
		catch(\Common\Mail\Transport\Exception $e)
		{
			switch($e->getCode()) {
				case(\Common\Mail\Transport\Exception::CODE_SWITCHED_TO_NATIVE_METHOD):
					$this->_log_delivery(Delivery::STATE_FAILURE, $e->getPrevious());
					$this->_log_delivery(Delivery::STATE_SUCCESS, $e);
					$result = true;
					break;
				default:
					$this->_log_delivery(Delivery::STATE_FAILURE, $e);
					$result = false;
					break;
			}
		}
		catch (\Exception $e)
		{
			$this->_log_delivery(Delivery::STATE_FAILURE, $e);
			$result = false;
		}
		if ($result)
		{
			if (ORM::factory('Common\Model\Task\Message\Delivery')->is_first_delivery_for_user($this->user) &&
				 $this->user->inviter_user_id)
			{
				$affiliate = Model::factory('Common\Model\Affiliate');
				/* @var \Common\Model\Affiliate $affiliate */
				$affiliate->affect_user_invitation($this->user->inviter, $this->user);
			}
			if (!$this->free)
			{
				// omit spending for superadmin
				$this->user->get_balance()->spend();
			}
		}

		return $result;
	}

	public function reschedule()
	{
		// smart rescheduling :)
		if(!$this->active)
		{
			return false;
		}

		if(!$this->repeated)
		{
			return false;
		}

		$datetime1 = new DateTime($this->exec_date);
		$datetime2 = new DateTime('now');

		$interval = $datetime1->diff($datetime2);

		$measure_db = false;
		$measure_prop =  false;
		$interval_add = 0;

		switch($this->repeat_interval_measure) {
			case('year'): default;
			$measure_db = 'YEAR';
			$measure_prop = 'y';
				break;
			case('month'):
			$measure_db = 'MONTH';
			$measure_prop = 'm';
				break;
			case('week'):
			$measure_db = 'DAY';
			$measure_prop = 'd';
			$interval_add = 6;
				break;
			case('day'):
			$measure_db = 'DAY';
			$measure_prop = 'd';
				break;
		}

		$value = $interval->$measure_prop + $interval_add + 1;

		//echo 'exec_date + INTERVAL ' . $value . ' ' . $measure_db;

		$this->exec_date = new Database_Expression('exec_date + INTERVAL ' . $value . ' ' . $measure_db);

		$this->save();

		return true;
	}

	public function mail($mail = NULL)
	{
		if (!is_null($mail))
		{
			$this->_mail = $mail;
			// mail is directly given: assign properties from it
			$this->assign_properties_from_mail($mail);
			return $this;
		}
		else
		{
			if (!$this->_mail)
			{
				$this->_mail = $this->create_mail();
			}

			return $this->_mail;
		}
	}

	public function mail_transport($mail_transport = NULL)
	{
		if (!is_null($mail_transport))
		{
			$this->_mail_transport = $mail_transport;
			return $this;
		}
		else
		{
			if (!$this->_mail_transport)
			{
				$this->_mail_transport = $this->create_mail_transport();
			}
			return $this->_mail_transport;
		}
	}

	public function create_mail()
	{
		// should be moved in Email class or its extension...

		// create mail using user\'s settings
		$mail = Email::factory();

		$user = ORM::factory('Common\Model\User', $this->user_id);
		/* @var $user \Common\Model\User */

		// creating default template
		$template = ORM::factory('Common\Model\Mail\Template');
		/* @var Template $template */

		// getting variables map (variables is default ones, from Common\Model\Mail\Template)
		$map = $template->get_variables_map();
		$data = array(
			'my_first_name' => $user->first_name,
			'my_last_name' => $user->last_name,
			'recipient_name' => $this->recipient_name,
			'second_recipient_name' => $this->second_recipient_name,
		);

		$this->mail_subject = $template->parse_variables($this->mail_subject, $map, $data);
		$this->mail_body = $template->parse_variables($this->mail_body, $map, $data);

		$mail->subject($this->mail_subject);

		$mime = preg_match('#<.+?>#', $this->mail_body)? 'text/html' : 'text/plain';
		$mail->message($this->mail_body, $mime);

		$mail->to(array($this->recipient_email => $this->recipient_name));

		// Fixing "from" field, for some SMTP its important
		$transport = $this->mail_transport();
		$smtp_config = $transport->config();

		if ($transport->method() == Transport::METHOD_SMTP &&
			isset($smtp_config['smtp_user']) &&
			strpos($smtp_config['smtp_user'], '@') !== FALSE)
		{
			$from_email = $smtp_config['smtp_user'];
		}
		else
		{
			$from_email = $user->email;
		}

		$mail->from(
			$from_email,
			$user->format_name()
		);

		return $mail;
	}

	public function create_mail_transport()
	{
		return Transport::create_for_user($this->user);
	}

	public function assign_properties_from_mail(Email $mail)
	{
		$to = $mail->raw_message()->getTo();

		foreach($to as $key => $value){
			if(!$value) {
				$value = $key;
			}
			// overwrite in last cycle
			$this->recipient_name = $value;
			$this->recipient_email = $key;
		};

		$this->mail_subject = $mail->raw_message()->getSubject();

		$body_parts = array();
		$body_parts []= $mail->raw_message()->getBody();

		foreach ($mail->raw_message()->getChildren() as $child)
		{
			$body_parts []= $child->getBody();
		}

		$this->mail_body = implode("\n", $body_parts);
	}

	public function delivery(Delivery $delivery = NULL)
	{
		if (!is_null($delivery))
		{
			$this->_delivery = $delivery;
		}
		else
		{
			return $this->_delivery;
		}
	}

	/**
	 * Get calculated exec time for this message
	 * @return number
	 */
	public function get_exec_time_utc()
	{
		if (!empty($this->user))
		{
			$user = $this->user;
		}
		else
		{
			$tz_offset = 0;
		}

		if (!empty($user->timezone))
		{
			$tz_offset = Timezone::get_timezone_offset($user->timezone->name);
		}
		else
		{
			$tz_offset = 0;
		}

		$send_offset_hours = Kohana::$config->load('common')->get('task_message_send_offset_hours');

		// We should _substract_ tz_offset from date + send_offset_hours, because +3:00 means that city is forward from utc,
		// then we have to send early
		$retval = strtotime($this->exec_date) + ($send_offset_hours * 3600) - $tz_offset;
		return $retval;
	}

	public function import_csv($file_name)
	{
		$user = AuthHelper::get_current_user();
		// get current user

		$templates = ORM::factory('Common\Model\Mail\Template')->where_current_user()->order_by('id', 'ASC')->find_all();

		if(count($templates) == 0) {
			throw new \Common\Model\Task\Message\CsvImport\Exception('There is no defined user templates', \Common\Model\Task\Message\CsvImport\Exception::CODE_NO_USER_TEMPLATES);
		} else {
			$template = $templates[0];
			/* @var $template \Common\Model\Mail\Template */
		}

		$csv = CSV::factory($file_name)->parse();
		// read csv by library

		$columns = $csv->titles();

		if(count($columns) != self::CSV_COLUMNS_COUNT) {
			throw new \Common\Model\Task\Message\CsvImport\Exception(vsprintf('Wrong csv file given, columns count must be exactly "%s"', array(self::CSV_COLUMNS_COUNT)), \Common\Model\Task\Message\CsvImport\Exception::CODE_NO_USER_TEMPLATES);
		}

		$added_ids = array();

		foreach($csv->rows() as $row){
			// line by line

			// create && save task messages
			$item = ORM::factory(__CLASS__);
			/* @var $item \Common\Model\Task\Message */

			$item->user_id = $user->id;

			$item->exec_date = $row[$columns[0]];
			$item->recipient_name = $row[$columns[1]];
			$item->recipient_email = $row[$columns[2]];
			$item->second_recipient_name = $row[$columns[3]];

			// template will be processed during exec()
			$item->mail_subject = $template->subject;
			$item->mail_body = $template->body;

			// check
			$item->check();

			// save it
			$item->save();

			$added_ids[$item->id] = TRUE;
			/*
			try {
				$item->check();
			} catch(\Exception $e) {
				var_dump($e->errors());
				exit;
			}
			*/
		}

		// return newly created ids
		return $added_ids;
	}

	public static function get_exec_date_interval_expression($addition_interval_sql = '')
	{
		$send_offset_hours = Kohana::$config->load('common')->get('task_message_send_offset_hours');
		return array(
			new Database_Expression('NOW()'),
			'BETWEEN',
			new Database_Expression('exec_date - INTERVAL 11 HOUR + INTERVAL ' . $send_offset_hours . ' HOUR ' . $addition_interval_sql .
				' AND exec_date + INTERVAL 14 HOUR + INTERVAL ' . $send_offset_hours . ' HOUR ' . $addition_interval_sql )
		);
	}

	public static function get_messages_too_early(\Traversable $models)
	{
		$aggr = array();

		foreach($models as $model) {
			/* @var $model Message */
			$exec_time = $model->get_exec_time_utc();

			if(time() < $exec_time){
				$aggr[$model->id]= $model;
			}
		}

		return $aggr;
	}

	protected function _log_delivery($state, \Exception $exception = NULL)
	{
		$delivery_model = ORM::factory('Common\Model\Task\Message\Delivery');
		/* @var Common\Model\Task\Message\Delivery $delivery*/

		$delivery = $delivery_model->create_for_message($this, $state, $exception);

		try {
			$delivery->save();
		} catch(Exception $e) {
			// FIXME
			echo $e->getMessage();
			exit;
		}
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