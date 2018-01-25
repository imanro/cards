<?php

class Flasher {
	const MESSAGE_INFO = 'info';

	const MESSAGE_ERROR = 'danger';

	const MESSAGE_SUCCESS = 'success';

	const MESSAGE_WARNING = 'warning';

	protected static $_messages;

	/**
	 * @param string $message
	 */
	public static function add_message( $message, $class = self::MESSAGE_INFO )
	{
		if(is_null(self::$_messages)){
			self::_init_messages();
		}

		$array = self::$_messages->get( 'user_message' );
		if( is_array( $array ) ) {
			$array[] = array(
				$message,
				$class
			);
		} else {
			$array = array(
				array(
					$message,
					$class
				)
			);
		}

		self::$_messages->set( 'user_message', $array );
	}

	/**
	 *
	 * @return array
	 */
	public static function get_messages()
	{
		if(is_null(self::$_messages)){
			self::_init_messages();
		}

		return self::$_messages->get_once( 'user_message' );
	}

	protected static function _init_messages()
	{
		self::$_messages = Session::instance();
	}

}