<?php

namespace Common\Model\Task\Message;

use ORM;
use Kohana;
use Acl\Dac\ResourceInterface;
use Text;
use Common\Model\Task\Message;
use Common\Model\Role;
use Common\Helper\Auth as AuthHelper;
use DB;
use Database_Result;
use Common\Model\User;

/**
 * @author manro
 *
 * @property int $id
 * @property string $create_time
 * @property int $task_message_id
 * @property string $state
 * @property string $error_text
 */
class Delivery extends ORM implements ResourceInterface {

	const STATE_SUCCESS = 'success';

	const STATE_FAILURE = 'failure';

	protected $_table_name = 'task_message_delivery';

	protected $_table_columns = array(
		'id' => null,
		'create_time' => null,
		'task_message_id' => null,
		'state' => null,
		'error_text' => null,
	);

	protected $_belongs_to = array(
		'task_message' => array('model' => 'Common\Model\Task\Message',
			'foreign_key' => 'task_message_id'),
	);

	public function scope_last_for_messages(Database_Result $result)
	{
		$ids = $this->extract_column_values($result, 'id');
		if (count($ids))
		{
			$this->join(DB::expr('(SELECT task_message_id, MAX(create_time) create_time from `' .
				 $this->table_name() . '` WHERE task_message_id IN(' . implode(',', $ids) .
				 ') GROUP BY task_message_id) t2'), 'INNER')->on('delivery.task_message_id', '=', 't2.task_message_id')->on('delivery.create_time', '=', 't2.create_time');
		}
		return $this;
	}

	public function join_last_delivery_to_messages_by_condition(Message $message_model, $task_condition)
	{
			$message_model->join(DB::expr('(SELECT a.task_message_id, MAX(a.create_time) delivery_create_time from `' .
				 $this->table_name() . '` a JOIN `' . $message_model->table_name() . '` b ON b.id=a.task_message_id WHERE ' . $task_condition .' GROUP BY task_message_id) t2'), 'LEFT')->
			on('t2.task_message_id', '=', 'message.id')->
			join(array($this->table_name(), 'last_delivery'), 'LEFT')->
			on('last_delivery.task_message_id', '=', 'message.id')->
			on('t2.delivery_create_time', '=', 'last_delivery.create_time');
	}

	public function join_deliveries_count_to_user(User $user_model, $condition = '')
	{
		if($condition){
			$condition = 'WHERE ' . $condition;
		}

		$user_model->select('delivery_count.delivery_count')
		->join(DB::expr('(select count(1) delivery_count, max(user_id) as user_id from task_message_delivery a join task_message b on b.id=a.task_message_id ' . $condition . ' group by user_id) as delivery_count'), 'LEFT')->
		on('delivery_count.user_id', '=', 'user.id');
		return $this;
	}

	/**
	 * @see \Common\Classes\Acl\Dac\ResourceInterface::get_owner_id()
	 */
	public function get_owner_id()
	{
		return $this->user_id;
	}

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/task-message-delivery');
	}

	public function create_for_message(Message $message, $state, \Exception $exception = null)
	{
		$this->task_message_id = $message->id;
		$this->state = $state;

		if(!is_null($exception))
		{
			$this->error_text = Text::limit_chars(vsprintf('%s[%s]: %s', array(get_class($exception), $exception->getCode(), $exception->getMessage())), 252, '...');
		}

		return $this;
	}

	public function pull_into_tasks(Database_Result $deliveries, $tasks)
	{
		$assoc = $deliveries->as_array('task_message_id');

		foreach($tasks as $task){
			/* @var Common\Model\Task\Message */
			if(isset($assoc[$task->id])) {
				$task->delivery($assoc[$task->id]);
			}
		}
	}

	public function search(array $values = NULL)
	{
		if (!is_null($values) && is_array($values))
		{
			if (!empty($values['from']))
			{
				$this->where('delivery.create_time', '>=', $values['from']);
			}
			if (!empty($values['to']))
			{
				$this->where('delivery.create_time', '<=', DB::expr('DATE_ADD(:value, INTERVAL 86399 SECOND)', array(':value' => $values['to'])));
			}

			if (AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN))
			{
				if (!empty($values['user_id']))
				{
					$this->where('task_message.user_id', '=', $values['user_id']);
				}
			}
		}
	}

	public static function is_first_delivery_for_user(User $user)
	{
		return ORM::factory(__CLASS__)->
		with('task_message')->
		where('task_message.user_id', '=', $user->id)->
		where('delivery.state', '=', self::STATE_SUCCESS)->
		count_all() <= 1;
	}
}