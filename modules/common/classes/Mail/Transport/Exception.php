<?php

namespace Common\Mail\Transport;

class Exception extends \Exception {
	const CODE_SENDING_FAILURE = 2;

	const CODE_SMTP_SENDING_FAILURE = 3;

	const CODE_NATIVE_SENDING_FAILURE = 4;

	const CODE_SWITCHED_TO_NATIVE_METHOD = 5;
}