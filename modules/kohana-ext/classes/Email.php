<?php

class Email extends Kohana_Email {
    /**
     * Creates a SwiftMailer instance.
     *
     * @param string $config_group
     * @return object Swift object
     */
	public static function mailer($config_group = 'default', Swift_Transport $transport = NULL, $force_reload = FALSE)
	{
		if (!is_null($transport))
		{
				// force using given transport
				Email::$_mailer = Swift_Mailer::newInstance($transport);
		}
		else
		{
			if (!Email::$_mailer || $force_reload)
			{
				// Load email configuration, make sure minimum defaults are set
				$config = Kohana::$config->load('email')->get($config_group);

				// Extract configured options


				if ($config['driver'] === 'smtp')
				{
					// Create SMTP transport
					$transport = Swift_SmtpTransport::newInstance($config['options']['hostname']);

					if (isset($config['options']['port']))
					{
						// Set custom port number
						$transport->setPort($config['options']['port']);
					}

					if (isset($config['options']['encryption']))
					{
						// Set encryption
						$transport->setEncryption($config['options']['encryption']);
					}

					if (isset($config['options']['username']))
					{
						// Require authentication, username
						$transport->setUsername($config['options']['username']);
					}

					if (isset($config['options']['password']))
					{
						// Require authentication, password
						$transport->setPassword($config['options']['password']);
					}

					if (isset($config['options']['timeout']))
					{
						// Use custom timeout setting
						$transport->setTimeout($config['options']['timeout']);
					}
				}
				elseif ($config['driver'] === 'sendmail')
				{
					// Create sendmail transport
					$transport = Swift_SendmailTransport::newInstance();

					if (isset($config['options']['command']))
					{
						// Use custom sendmail command
						$transport->setCommand($config['options']['command']);
					}
				}
				else
				{
					// Create native transport
					$transport = Swift_MailTransport::newInstance();

					if (isset($config['options']['params']))
					{
						// Set extra parameters for mail()
						$transport->setExtraParams($config['options']['params']);
					}
				}

				// Create the SwiftMailer instance
				Email::$_mailer = Swift_Mailer::newInstance($transport);
			}
		}

		return Email::$_mailer;
	}
}