<?php

namespace Common\Model\User;

use Kohana;
use ORM;
use Common\Model\User;
use Common\Messenger;

/**
 * @property int $user_id
 * @property int $value
 *
 * @property User $user
 */
class Balance extends ORM {

	// NB: composite PK!
	protected $_primary_key = 'user_id';

	protected $_table_name = 'user_balance';

	protected $_table_columns = array(
		'user_id' => array(
			'data_type' => 'int',
		),
		'value' => array(
			'data_type' => 'int',
		),
	);

	protected $_belongs_to = array(
		'user' => array(
			'model' => 'User'
		)
	);

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/user-balance');
	}

	public function increase($value = 1)
	{
		if ($value <= 0 || !is_int($value))
		{
			throw new \Common\Model\User\Balance\Exception(vsprintf('Wrong value for decrease "%s", must be positive integer', array(
				$value
			)));
		}
		else
		{
			$this->value += $value;
		}

		$this->save();

		return $this;
	}

	public function decrease($value = 1)
	{
		if ($value <= 0 || !is_int($value))
		{
			throw new \Common\Model\User\Balance\Exception(vsprintf('Wrong value for decrease "%s", must be positive integer', array(
				$value
			)));
		}
		else if ($this->value - $value < 0)
		{
			throw new \Common\Model\User\Balance\Exception(vsprintf('Too small value on user\'s "%s" balance: "%s", unable to substract "%s"', array(
				$this->user_id,
				$this->value,
				$value
			)), \Common\Model\User\Balance\Exception::CODE_SMALL_VALUE);
		}
		else
		{
			$this->value -= $value;
		}

		$this->save();

		$milestones = array(
			50 => true,
			10 => true,
			3 => true,
		);

		if(isset($milestones[$this->value])){
			Messenger::notify_alert_balance($this->user);
		}

		return $this;
	}

	public static function create_for_user(User $user)
	{
		$model = ORM::factory('Common\Model\User\Balance');

		$model->user_id = $user->id;

		$model->value = Kohana::$config->load('settings')->get('default_balance');

		return $model;
	}

	public static function get_for_user(User $user)
	{
		$balance = $user->balance;

		if(is_null($balance) || !$balance->loaded()){
			$balance = self::create_for_user($user);
			$balance->save();
		}

		return $balance;
	}

	public function check_value($value = 1)
	{
		return $this->value - $value >= 0;
	}

	/**
	 * Just alias to decrease
	 * @param number $value
	 */
	public function spend($value = 1)
	{
		$this->decrease($value);
	}

	public function save_for_user(User $user)
	{
		// remove old balance
		try {
			$user->balance->delete();
		} catch(\Exception $e) {
			;
		}

		$this->user_id = $user->id;
		$this->save();
		return $this;
	}
}