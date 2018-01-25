<?php defined('SYSPATH') OR die('No direct script access.');

class HTTP_Exception_500 extends HTTP_Exception {

	/**
	 * @var   integer    HTTP 500 Internal Server Error
	 */
	protected $_code = 500;

	public function __construct($message = NULL, array $variables = NULL, Exception $previous = NULL)
	{
		if(!is_null($previous)){
			if($previous instanceof ORM_Validation_Exception) {
				$message .= $this->_get_orm_errors_flatten($previous);
			} else {
				if(Kohana::$environment != Kohana::PRODUCTION){
					throw $previous;
					$message .= '<p>' . $previous->getMessage() . '</p>';
				}
			}
		}
		parent::__construct($message, $variables, $previous);
	}

	protected function _get_orm_errors_flatten(\ORM_Validation_Exception $e)
	{
		return '<ul><li>' . implode('</li><li>', $e->errors('validation', TRUE)) . '</li></ul>';
	}
}
