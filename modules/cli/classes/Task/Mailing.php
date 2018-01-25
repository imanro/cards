<?php

namespace Cli\Task;

use Kohana;
use Minion_Task;
use Common\Messenger;
use Minion_CLI;

class Mailing extends Minion_Task {
	protected function _execute(array $params)
	{
		Messenger::mailing_users();
	}
}