<?php

namespace Cli\Task;

use Minion_Task;
use Minion_CLI;
use DB;
use ORM;
use Common\Model\Task\Message;
use Common\Model\Task\Message\Delivery;
use Database;

class SendMessages extends Minion_Task {
	protected function _execute(array $params)
	{
		// working in UTC
		date_default_timezone_set('UTC');
		Database::instance()->query(NULL, 'SET time_zone=' . Database::instance()->quote('+00:00') . ';');

		/*
		 * Условие для отправки:
		 *
		 * 1) Сообщение активно
		 * 2) У сообщения дата отправки (в полном диапазоне временных сдвигов для временных зон - от -11 до + 14) + общее для всех время отправки (7 часов) раньше или = сейчас (UTC)
		 * 3) У сообщения не было последней отправки или она завершилась неудачей
		 */
		$interval_expression = Message::get_exec_date_interval_expression();

		$delivery_model = ORM::factory('Common\Model\Task\Message\Delivery');
		$inner_condition = 'active=1 AND ' . implode(' ', $interval_expression);

		$model = ORM::factory('Common\Model\Task\Message');

		$delivery_model->join_last_delivery_to_messages_by_condition($model, $inner_condition);

		$model->where('active', '=', '1')->

		// taking messages in period of all posible time offsets from server timezone
		and_where_open()->
		where($interval_expression[0], $interval_expression[1], $interval_expression[2])->
		and_where_close()->

		and_where_open()->
		where('last_delivery.state', 'IS', DB::expr('NULL'))->
		or_where('last_delivery.state', '!=', Delivery::STATE_SUCCESS)->
		and_where_close();

		$models = $model->find_all();

		Minion_CLI::write(vsprintf('Found %s messages for sending', array(count($models))));
		Minion_CLI::write(vsprintf('Now time is (UTC): %s', date('r')));


		foreach($models as $model){
			Minion_CLI::write(vsprintf('For message #%s exec date in UTC: %s', array($model->id, date('r',$model->get_exec_time_utc()))));
		}

		$skip = Message::get_messages_too_early($models);

		foreach($skip as $model){
			Minion_CLI::write(vsprintf('Skipping message #%s, too early to send', array($model->id)));
		}

		$processed = array();
		// in case that message apears twice (for two errors in the same time join may produce duplicates) register processed

		foreach($models as $model){
			if(!empty($processed[$model->id]) || !empty($skip[$model->id])){
				continue;
			}

			try
			{
				if ($model->exec())
				{
					Minion_CLI::write(vsprintf('Message #%s processed successfully', array(
						$model->id
					)));
				}
				else
				{
					Minion_CLI::write(vsprintf('Message #%s processed with error', array(
						$model->id
					)));
				}
			}
			catch (\Exception $e)
			{
				Minion_CLI::write(vsprintf('Message #%s sending finished with error: %s [%s]: %s', array(
					$model->id,
					get_class($e),
					$e->getCode(),
					$e->getMessage()
				)));
			}
			$processed[$model->id] = true;
		}
		exit;
	}
}
