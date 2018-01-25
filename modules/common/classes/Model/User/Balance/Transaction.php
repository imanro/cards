<?php

namespace Common\Model\User\Balance;

use Kohana;
use ORM;
use Common\Model\User;

/**
 * @author manro
 *
 * @property int $id
 * @property string $type
 * @property int $value
 * @property string $subject
 * @property string $resource
 * @property int $user_id
 * @property string $create_time
 *
 * @property \Common\Model\User $user
 */
class Transaction extends ORM {

	const SUBJECT_AFFILIATE_INVITE = 'affiliate_invite';

	const TYPE_INCOME = 'income';

	const TYPE_OUTCOME = 'outcome';

	protected $_table_name = 'user_balance_transaction';

	protected $_table_columns = array(
		'id' => null,
		'type' => null, // debet/credit
		'value' => array(
			'data_type' => 'integer',
		),
		'subject' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		),// affiliate_invite
		'resource' => array(
			'data_type' => 'string',
			'is_nullable' => TRUE
		), // invite_user_id
		'user_id' => null,
		'create_time' => null
	);

	protected $_belongs_to = array(
		'user' => array('model' => 'User'),
		'affiliate_user' => array('model' => 'User', 'foreign_key' => 'resource')
	);

	/**
	 * @var User Cached user
	 */
	protected $_invited_user;

	public function init()
	{
		if(!$this->loaded()){
			$this->value = 0;
		}
	}

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/user-balance-transaction');
	}

	public function get($column)
	{
		if($column == 'invited_user') {
			return $this->invited_user();
		} else {
			return parent::get($column);
		}
	}
	public function apply()
	{
		$user = $this->user;

		if(!$user || !$user->loaded())
		{
			throw new \Exception('Unable to find transaction\'s user');
		}

		$balance = $this->user->get_balance();

		if ($this->type == self::TYPE_INCOME)
		{
			$balance->increase($this->value);
		}
		else if ($this->type == self::TYPE_OUTCOME)
		{
			$balance->decrease($this->value);
		}
		else
		{
			throw new \Exception('Unknown transaction type');
		}

		return $this;
	}


	public function invited_user(User $user = NULL)
	{
		if (!is_null($user))
		{
			$this->_invited_user = $user;
		}
		else
		{
			return $this->_invited_user;
		}
	}
}