<?php

namespace Common\Model\User;

use ORM;
use DB;
use Validation;
use Text;
use Database_Expression;

class Token extends ORM {

	protected $_table_name = 'user_token';

	protected $_table_columns = array(
		'id' => NULL,
		'user_id' => NULL,
		'user_agent' => NULL,
		'token' => NULL,
		'create_time' => NULL,
		'expires' => NULL
	);

	// Relationships
	protected $_belongs_to = array(
		'user' => array('model' => 'User'),
	);

	/**
	 * Handles garbage collection and deleting of expired objects.
	 *
	 * @return  void
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (mt_rand(1, 100) === 1)
		{
			// Do garbage collection
			$this->delete_expired();
		}

		if (strtotime($this->expires) < time() AND $this->_loaded)
		{
			// This object has expired
			$this->delete();
		}
	}

	public function filters()
	{
		return array('expires' => array(
			array(array('Date', 'to_format'), array(':value', 'Y-m-d H:i:s'))
		));
	}

	/**
	 * Deletes all expired tokens.
	 *
	 * @return  ORM
	 */
	public function delete_expired()
	{
		// Delete all expired tokens
		DB::delete($this->_table_name)
			->where('expires', '<', new Database_Expression('NOW()'))
			->execute($this->_db);

		return $this;
	}

	public function create(Validation $validation = NULL)
	{
		$this->token = $this->create_token();
		return parent::create($validation);
	}

	protected function create_token()
	{
		do
		{
			$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
		}
		while (ORM::factory('Common\Model\User\Token', array('token' => $token))->loaded());

		return $token;
	}

} // End Auth User Token Model