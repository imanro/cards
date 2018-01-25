<?php

namespace Common;

use Kohana;
use Common\Model\User;
use Common\Mail\Transport;
use ORM;
use Email;
use Common\Model\Mail\Template;
use Route;
use URL;
use Database_Expression;
use Common\Model\Timezone;
use DateTime;
use DateInterval;
use Common\Module;
use View;

class Messenger {

	public static function notify_signup_email_confirm(User $user)
	{
		// create email
		$mail = self::_create_email();

		// get standart template for this
		$template = Template::create_template_signup_email_confirm();
		/* @var Template $template */

		$link = URL::site(Route::get('default')->uri(array('controller' => 'auth', 'action' => 'mail-confirm', 'hash' => $user->mail_confirm_hash )), 'http');

		/* bind variables */
		$template->variables_data(array(
			'link' => $link,
			'site_name' => Kohana::$config->load('settings')->get('site_name'),
		));

		$template->process();
		$template->apply_to($mail);

		$mail->to($user->email, $user->format_name());

		// create task for sending message
		$task = self::_create_task_message();

		// set email to task
		$task->mail($mail);

		// send it
		return $task->exec();
	}

	public static function notify_password_reset_ask(User $user)
	{
		// create email
		$mail = self::_create_email();

		// get standart template for this
		$template = Template::create_template_password_reset_ask();
		/* @var Template $template */

		$link = URL::site(Route::get('default')->uri(array('controller' => 'auth', 'action' => 'password-reset', 'hash' => $user->password_reset_hash )), 'http');

		/* bind variables */
		$template->variables_data(array(
			'link' => $link,
			'site_name' => Kohana::$config->load('settings')->get('site_name'),
		));

		$template->process();

		$template->apply_to($mail);

		$mail->to($user->email, $user->format_name());

		// create task for sending message
		$task = self::_create_task_message();

		// set email to task
		$task->mail($mail);

		// send it
		return $task->exec();
	}

	public static function send_user_message(User $user, $to, $subject, $body)
	{
		// create email
		$mail = self::_create_email($user);

		$mail->to($to);
		$mail->subject($subject);
		$mail->message($body);

		// create task for sending message
		$task = self::_create_task_message($user);

		// set email to task
		$task->mail($mail);

		// send it
		return $task->exec();
	}

	public static function mailing_users()
	{
				// get all users
		$users = ORM::factory('Common\Model\User')->find_all();

		// get parameter when send letters
		$mailing_time_hours = Kohana::$config->load('common')->get('mailing_time_hours');
		$mailing_time_weekday = Kohana::$config->load('common')->get('mailing_time_weekday');

		$variables_data_common = array('site_name' => Kohana::$config->load('settings')->get('site_name'));

		// for each user
		foreach($users as $user) {
			/* @var \Common\Model\User $user */
			$user_hours = (int)Timezone::get_timezone_current_time($user->timezone->name)->format('H');
			$user_weekday = (int)Timezone::get_timezone_current_time($user->timezone->name)->format('N');

			$now = new DateTime('now');
			$upcoming = new DateInterval('P10D');

			$upcoming_start_datetime = new DateTime($now->format('Y-m-d') . ' 00:00');
			$upcoming_end_datetime = new DateTime($now->add($upcoming)->format('Y-m-d') . ' 00:00');

			if (
//				1 ||
				( $user_hours == $mailing_time_hours && $user_weekday == $mailing_time_weekday))
			{
				// does this user have active tasks for next week?
				$messages = $user->messages->
					where('active', '=', 1)->
					where('exec_date', '>=', $upcoming_start_datetime->format('Y-m-d H:i:s'))->
					where('exec_date', '<', $upcoming_end_datetime->format('Y-m-d H:i:s'))->find_all();

				if (count($messages) > 0)
				{
					// rendering schedule
					$schedule = View::factory('partials/task-messages', array(
						'messages' => $messages
					), Module::$name);
					// composing mail according to template
					$template = Template::create_template_mailing_upcoming_messages();

					$template->variables_data(array_merge($variables_data_common, array(
						'schedule' => $schedule->render(),
						'my_first_name' => $user->format_name()
					)));

					$template->process();

					$mail = self::_create_email($user);
					$template->apply_to($mail);
					$mail->to($user->email, $user->format_name());

					// create task for sending message
					$task = self::_create_task_message();

					// set email to task
					$task->mail($mail);

					// send it
					$task->exec();
				}
			}
		}
	}

	public static function notify_alert_balance(User $user)
	{
		$amount = $user->get_balance()->value;
		$template = Template::create_template_alert_balance();
		$template->variables_data(array(
			'amount' => $amount,
			'link' => URL::site(Route::get('affiliate_signup')->uri(array('code' => $user->affiliate_code )), 'http'),
			'affiliate_site_section_link' => URL::site(Route::get('default')->uri(array('controller' => 'affiliate', 'action' => 'index')), 'http'),
			'site_name' => Kohana::$config->load('settings')->get('site_name'),
		));

		$template->process();

		$mail = self::_create_email();

		$template->apply_to($mail);

		$mail->to($user->email, $user->format_name());

		// create task for sending message
		$task = self::_create_task_message();

		// set email to task
		$task->mail($mail);

		// send it
		return $task->exec();
	}

	/**
	 * @return Common\Model\Task\Message
	 */
	protected static function _create_task_message(User $user = NULL)
	{
		$task = ORM::factory('Common\Model\Task\Message');
		/* @var Common\Model\Task\Message $task  */

		if(is_null($user)) {
			$user = ORM::factory('Common\Model\User', User::ID_ROOT);
		}

		$task->user_id = $user->id;
		$task->hidden = '1';
		$task->free = '1'; // not spend balance
		$task->repeated = '0';
		$task->active = '0'; // exclude from cron

		$task->exec_date = new Database_Expression('NOW()');

		return $task;
	}

	protected static function _create_email(User $user = NULL)
	{
		// should be moved in Email class...
		$common_config = Kohana::$config->load('common')->get('mail');

		if(is_null($user)) {
			$user = ORM::factory('Common\Model\User', User::ID_ROOT);
			$from_name = $common_config['from_name'];
		} else {
			$from_name = $user->format_name();
		}

		if(!$user || !$user->loaded()){
			throw new \Exception('Unable to find user for sending emails');
		}

		$transport = self::_create_transport($user);
		$smtp_config = $transport->config();

		if ($transport->method() == Transport::METHOD_SMTP &&
			isset($smtp_config['smtp_user']) &&
			strpos($smtp_config['smtp_user'], '@') !== FALSE)
		{
			// Fix from for some smtp servers
			$from_email = $smtp_config['smtp_user'];
		}
		else
		{
			$from_email = $common_config['from_email'];
		}

		$email = Email::factory();
		$email->from(
			$from_email,
			$from_name
		);

		return $email;
	}

	protected static function _create_transport(User $user)
	{
		return Transport::create_for_user($user);
	}
}