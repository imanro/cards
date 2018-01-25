<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Remind task
 */
class Task_Remind extends Minion_Task {

	/**
	 * @param array $params
	 * @return void
	 */
	protected function _execute(array $params)
	{
		/*
		 * По поводу временных зон: фреймворк стартует с бутстрапом, в котором установка временной зоны для ПХП по умолчанию отключена (= берутся серверные настройки)
		 * Для mysql мы также не задействуем корректировку, и, опять таки, берутся серверные установки временной зоны
		 *
		 * Значит:
		 * Из БД к нам приходят даты в серверной временной зоне
		 * При конвертации в таймстамп, согласно date_get_default_timezone(), мы интерпретируем дату в серверной временной зоне, и она приводятся из серверной временной зоны в UTC (или того, что указано в /etc/timezone)
		 *
		 * Сравниваются таймстампы UTC vs. UTC
		 *
		 * При отсылке письма мы временно устанавливаем таймзону в таймзону пользователя, чтобы даты в письме отображались корректно
		 */

		// take all tasks which time is'nt over

		// set server to UTC while executing
		$tasks = ORM::factory( 'Task' )->where( 'state', '=', (string)Model_Task::STATE_ACTIVE )->find_all();
		//$tasks = ORM::factory( 'Task' )->find_all();

		// okay, for each task
		foreach( $tasks as $task ) {

			// take all alarms
			foreach( $task->alarms->find_all() as $alarm ) {

				//Minion_CLI::write( 'Id: ' . (string)$alarm->id );
				//Minion_CLI::write( 'Is processed: ' . (string)$alarm->is_processed );

				if( $alarm->is_processed || ! $alarm->value ) {
					continue;
				} else {


					if( $alarm->type == Model_Alarm::TYPE_REPEATING ) {
						Minion_CLI::write( vsprintf( 'processing repeating alarm #%s', array( $alarm->id ) ) );
						$this->_processAlarmRepeating($alarm);

					} else if( $alarm->type == Model_Alarm::TYPE_AUTOMATIC ) {
						Minion_CLI::write( vsprintf( 'processing automatic alarm #%s', array( $alarm->id ) ) );
						$this->_processAlarmAutomatic($alarm);

					} else {
						Minion_CLI::write( vsprintf( 'processing unitary alarm #%s', array( $alarm->id ) ) );
						$this->_processAlarmUnitary($alarm);
					}
					// check if it's time
				}
			}
		}
	}

	protected function _processAlarmUnitary( $alarm )
	{
		$task = $alarm->task;

		$end_time = strtotime( $task->end_time );

		$threshold = 3600; // 1 hour difference allowed

		// take absolute date
		switch( $alarm->measure ){
			default:
				return;
			break;
			case ( 'month' ):
				$alarm_time = $end_time - ( 86400 * 30 * (int)$alarm->value );
			break;
			case ( 'week' ):
				$alarm_time = $end_time - ( 86400 * 7 * (int)$alarm->value );
			break;
			case ( 'day' ):
				$alarm_time = $end_time - ( 86400 * (int)$alarm->value );
			break;
			case ( 'hour' ):
				$alarm_time = $end_time - ( 3600 * (int)$alarm->value );
			break;
			case ( 'minute' ):
				$alarm_time = $end_time - ( 60 * (int)$alarm->value );
			break;
		}

		if( time() >= $alarm_time && ( $alarm_time + $threshold >= time() ) ) {
			// if( true ) {
			Minion_CLI::write( 'Alarm # ' . $alarm->id );
			Minion_CLI::write( 'Task end time: ' . date( 'Y-m-d H:i:s', $end_time ) );
			Minion_CLI::write( 'Alarm time: ' . date( 'Y-m-d H:i:s', $alarm_time ) );

			try {
				$task->send( $alarm );
				$alarm->is_processed = 1;
				$alarm->save();
			} catch( Exception $e ) {
				Minion_CLI::write( $e->getMessage() );
				return;
			}
		}
	}

	protected function _processAlarmAutomatic( $alarm )
	{
		// 1) take end time - start time
		$task = $alarm->task;

		Minion_CLI::write( vsprintf( 'time now is: %s', array( preg_replace( '/\d{3}/', '$0 ', time() ) ) ) );

		$distance = strtotime( $task->end_time ) - strtotime( $task->create_time );

		Minion_CLI::write( vsprintf( 'distance is: %s', array( $distance ) ) );

		$threshold = 3600; // 1 hour difference allowed

		if( $distance > 0 && $alarm->amount ) {
					// 2) divide to amount = time of alarm
					$period = $distance / $alarm->amount;

					Minion_CLI::write( vsprintf( 'period is: %s', array( $period ) ) );

					// 3) defining absolute values for seconds
					$periods_abs = array();

					$p = strtotime( $task->create_time );

					for( $i = 0; $i < $alarm->amount; $i++ ) {
						$p += $period;
						$periods_abs []= $p;
						Minion_CLI::write( vsprintf( 'Absolute value for period #%s: %s', array( $i + 1, preg_replace( '/\d{3}/', '$0 ', $p ) ) ) );
					}

					// now, if $alarm->repeat_update_time is less than $p (there was no alarm for this period) and $p + threshold is bigger or eq time()
					for( $i = 0; $i < count( $periods_abs ); $i++ ) {
						$p = $periods_abs[ $i ];

						/**
						 * update time is PRE absolute
						 * And
						 * NOW() is POST absolute
						 */
						if( strtotime( $alarm->repeat_update_time ) < $p && time() >= $p ) {

							Minion_CLI::write( vsprintf( 'Repeat update time: %s', array( $alarm->repeat_update_time) ) );

							if( $p + $threshold >= time() ) {
								Minion_CLI::write( vsprintf( 'Time (%s) spend for period #%s (%s), sending', array( preg_replace( '/\d{3}/', '$0 ', time() ), $i + 1, preg_replace( '/\d{3}/', '$0 ', $p ) ))  );
								$task->send( $alarm );
								$alarm->repeat_update_time = date( 'Y-m-d H:i:s' );
								$alarm->save();
								break;

							} else {
								Minion_CLI::write( vsprintf( 'Too late (%s) for period #%s (%s), just updating update time', array( time(), $i + 1, $p ) ) );
								// just updating update_time in both cases
								$alarm->repeat_update_time = date( 'Y-m-d H:i:s' );
								$alarm->save();
								// yes
							}
						}
					}

		// 3) if this time is bigger, than amount, send

		//
		}
	}

	protected function _processAlarmRepeating( $alarm )
	{
		$task = $alarm->task;

		$update_time = strtotime( $alarm->repeat_update_time );

		switch( $alarm->measure ){
			default:
				return;
			break;

			case ( Model_Alarm::MEASURE_YEAR ):
				$alarm_time = $update_time + ( 86400 * 365 * (int)$alarm->value );
				break;

			case ( Model_Alarm::MEASURE_MONTH ):
				$alarm_time = $update_time + ( 86400 * 30 * (int)$alarm->value );
			break;

			case ( Model_Alarm::MEASURE_WEEK ):
				$alarm_time = $update_time + ( 86400 * 7 * (int)$alarm->value );
			break;

			case ( Model_Alarm::MEASURE_DAY ):
				$alarm_time = $update_time + ( 86400 * (int)$alarm->value );
			break;

			case ( Model_Alarm::MEASURE_HOUR ):
				$alarm_time = $update_time + ( 3600 * (int)$alarm->value );
			break;
		}
		if( time() >= $alarm_time ) {

			Minion_CLI::write( 'Alarm # ' . $alarm->id );
			Minion_CLI::write( 'Last update time: ' . date( 'Y-m-d H:i:s', $update_time ) );
			Minion_CLI::write( 'Alarm time: ' . date( 'Y-m-d H:i:s', $alarm_time ) );

			if( $this->_testWday( $alarm->repeat_wdays ) ) {


				Minion_CLI::write( 'Wday matched!' );

				try {
					$task->send( $alarm );
					$alarm->repeat_update_time = date( 'Y-m-d H:i:s', $alarm_time );

					$alarm->save();
				} catch( Exception $e ) {
					Minion_CLI::write( $e->getMessage() );
					return;
				}
			} else {
				Minion_CLI::write( 'Wday not matched, waiting' );
			}
		}

	}

	protected function _testWday( $wday )
	{
		$a = (int)date( 'w' );

		$assoc = array(
			0 => Model_Alarm::WDAY_SUNDAY,
			1 => Model_Alarm::WDAY_MONDAY,
			2 => Model_Alarm::WDAY_TUESDAY,
			3 => Model_Alarm::WDAY_WEDNESDAY,
			4 => Model_Alarm::WDAY_THURSDAY,
			5 => Model_Alarm::WDAY_FRIDAY,
			6 => Model_Alarm::WDAY_SATURDAY,
		);

		if( isset( $assoc[ $a ] ) ) {
			$b = $assoc[ $a ];
		} else {
			throw new Exception( 'unknown day returned by date()');
		}

		if( $b & $wday ) {
			return true;
		} else {
			return false;
		}

	}
}
