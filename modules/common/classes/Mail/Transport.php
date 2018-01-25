<?php

namespace Common\Mail;

use Kohana;
use Common\Module;
use Common\Model\User;
use Email;
use Arr;
use Swift_SmtpTransport;

class Transport {

	const METHOD_NATIVE = 'native';

	const METHOD_SMTP = 'smtp';

	protected $_config = array(
		'smtp_host' => NULL,
		'smtp_port' => NULL,
		'smtp_start_tls' => NULL,
		'smtp_tls_ssl' => NULL,
		'smtp_user' => NULL,
		'smtp_password' => NULL
	);

	protected $_method;

	public static function factory($method = self::METHOD_NATIVE, $config = NULL)
	{
		return new self($method, $config);
	}

	public static function create_for_user(User $user)
	{
		// check user's settings
		if(!empty($user->mail_smtp_host)) {
			$config = array(
				'smtp_host' => $user->mail_smtp_host,
				'smtp_port' => $user->mail_smtp_port ? $user->mail_smtp_port : 25,
				'smtp_start_tls' => $user->mail_smtp_start_tls,
				'smtp_tls_ssl' => $user->mail_smtp_tls_ssl,
				'smtp_user' => $user->mail_smtp_user,
				'smtp_password' => $user->mail_smtp_password
			);
			return self::factory(self::METHOD_SMTP, $config);
		}
		else
		{
			return self::factory(self::METHOD_NATIVE);
		}
	}
	public function __construct($method = self::METHOD_NATIVE, $config)
	{
		$this->method($method);
		$this->config($config);
	}

	public function method($method = NULL)
	{
		if (!is_null($method))
		{
			$this->_method = $method;
			return $this;
		}
		else
		{
			return $this->_method;
		}
	}

	public function config($config = NULL)
	{
		if (!is_null($config))
		{
			$this->_config = Arr::merge($this->_config, $config);
			return $this;
		}
		else
		{
			return $this->_config;
		}
	}

	public function send(Email $email)
	{
		switch($this->method()) {
			case(self::METHOD_SMTP):
				$email->mailer(NULL, $this->_create_swift_smtp_transport());
				try {
					return $email->send();
				} catch(\Exception $e ) {

					$email->mailer('default', NULL, TRUE);

					try {
						$retval = $email->send();
					} catch(\Exception $e) {
						throw new \Common\Mail\Transport\Exception($e->getMessage(), \Common\Mail\Transport\Exception::CODE_SENDING_FAILURE, $e);
					}

					// TODO: from username
					$smtp_e = new \Common\Mail\Transport\Exception($e->getMessage(), \Common\Mail\Transport\Exception::CODE_SMTP_SENDING_FAILURE, $e);
					$switch_e = new \Common\Mail\Transport\Exception(
						Kohana::message('system', 'message.mail_transport_switched_to_native', 'We switched to native delivery method because of wrong SMTP configuration', Module::$name),
						\Common\Mail\Transport\Exception::CODE_SWITCHED_TO_NATIVE_METHOD,
						$smtp_e);

					throw $switch_e;
					return $retval;

				}
				break;
			case(self::METHOD_NATIVE): default:
				try {
					return $email->send();
				} catch(\Exception $e) {
					throw new \Common\Mail\Transport\Exception($e->getMessage(), \Common\Mail\Transport\Exception::CODE_NATIVE_SENDING_FAILURE, $e);
				}
				break;
		}
	}

	protected function _create_swift_smtp_transport()
	{
		$config = $this->config();

		$transport = Swift_SmtpTransport::newInstance($config['smtp_host']);
		$transport->setPort($config['smtp_port']);

		if ($config['smtp_tls_ssl'])
		{
			$transport->setEncryption('ssl');
		}
		else if ($config['smtp_start_tls'])
		{
			$transport->setEncryption('tls');
		}

		$transport->setUsername($config['smtp_user']);
		$transport->setPassword($config['smtp_password']);
		$transport->setTimeout(10);
		return $transport;
	}
}