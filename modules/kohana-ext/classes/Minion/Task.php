<?php

abstract class Minion_Task extends Kohana_Minion_Task {

	public static $task_namespace = 'Cli\Task\\';

	public static function convert_task_to_class_name($task)
	{
		$task = trim($task);

		if (empty($task))
			return '';

		return self::$task_namespace . implode('\\', array_map('ucfirst', explode(Minion_Task::$task_separator, $task)));
	}
}