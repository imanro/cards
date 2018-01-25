<?php

namespace Cli\Task;

use Kohana;
use Minion_Task;
use Minion_CLI;
use DB;
use ORM;
use Common\Model\Task\Message;

class RescheduleMessages extends Minion_Task {
	protected function _execute(array $params)
	{
		$graceful_period_pending_hours = Kohana::$config->load('common')->get('task_message_graceful_period_pending_hours');
		$interval_expression = Message::get_exec_date_interval_expression('+ INTERVAL ' . $graceful_period_pending_hours . ' HOUR');

		$models = ORM::factory('Common\Model\Task\Message')->
		where('active', '=', '1')->
		where('repeated', '=', '1')->

		and_where_open()->
		where($interval_expression[0], $interval_expression[1], $interval_expression[2])->
		and_where_close()->

		find_all();

		/* @var $models Message[] */

		Minion_CLI::write(vsprintf('Found %s messages for rescheduling', array(count($models))));

		foreach($models as $model){
			try {
			if($model->reschedule()){
			Minion_CLI::write( vsprintf('Message #%s rescheduled successfully', array(
				$model->id
			)));
			} else {
				Minion_CLI::write(vsprintf('Message #%s resceduled finished with error', array(
					$model->id
				)));
			}
			} catch(\Exception $e) {
							Minion_CLI::write(vsprintf('Message #%s resceduled finished with error (%s)', array(
					$model->id, $e->getMessage()
				)));
			}
		}
	}
}
