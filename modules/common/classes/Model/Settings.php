<?php

namespace Common\Model;

use Kohana;
use Model;

class Settings extends Model {

	const DATA_TYPE_INT = 'int';

	const DATA_TYPE_BOOLEAN = 'bool';

	const DATA_TYPE_DOUBLE = 'double';

	const DATA_TYPE_FLOAT = 'float';

	const DATA_TYPE_STRING = 'string';

	protected $_loaded = false;

	protected $_group_name = 'settings';

	/**
	 * @var \Config\Group
	 */
	protected $_group;

	protected $_config = array(
		'default_balance' => 100,
		'bonuce_value_affiliate_invite' => 10,
		'site_url' => 'http://congrats.local',
		'site_name' => 'Cards',
		'credentials' => 'root@localhost.com',
	);

	protected $_data_config = array(
		'default_balance' => array(
			'data_type' => self::DATA_TYPE_INT
		),
		'bonuce_value_affiliate_invite' => array(
			'data_type' => self::DATA_TYPE_INT
		),
		'site_url' => array(
			'data_type' => self::DATA_TYPE_STRING
		),
		'site_name' => array(
			'data_type' => self::DATA_TYPE_STRING
		),
		'credentials' => array(
			'data_type' => self::DATA_TYPE_STRING
		)
	);

	public function config_value($key, $value = NULL)
	{
		if (is_null($value))
		{

		}
		else
		{
			$this->_data[$key] = $value;
		}
	}

	public function save()
	{
		if(!$this->loaded()){
			throw new \Exception(vsprintf('%s model must be loaded before save', array(__CLASS__)));
		}

		$group = $this->group();

		foreach($this->config() as $key => $value) {
			$group->set($key, $value);
		}

		return $this;
	}

	public function load()
	{
		$group = Kohana::$config->load($this->_group_name);
		$this->config($group->as_array());
		$this->group($group);
		$this->loaded(true);
		return $this;
	}

	public function loaded($value = NULL)
	{
		if (is_null($value))
		{
			return $this->_loaded;
		}
		else
		{
			$this->_loaded = $value;
			return $this;
		}
	}

	/**
	 * @param unknown $group
	 * @return \Config\Group|\Common\Model\Settings
	 */
	public function group($group = NULL)
	{
		if (is_null($group))
		{
			return $this->_group;
		}
		else
		{
			$this->_group = $group;
			return $this;
		}
	}


	public function config($key = NULL, $value = NULL)
	{
		if (!is_null($key))
		{
			if(!is_null($value)){
				// setter
				$this->_config[$key] = $value;
				$this->_typecast($key);
			}
			else
			{
				if (is_array($key))
				{
					// setter for all
					$this->_config = array_merge($this->_config, $key);
					$this->_typecast();
				}
				else
				{

					// getter for value
					return ((isset($this->_config[$key])) ? $this->_config[$key] : NULL);
				}
			}
		}
		else
		{
			// getter for all
			return $this->_config;
		}
	}

	protected function _typecast($only_key = NULL)
	{
		foreach($this->_data_config as $key => $config)
		{
			if(!is_null($only_key) && $key != $only_key){
				continue;
			}

			switch($config['data_type']){
				case (self::DATA_TYPE_STRING):
				default:
					$this->_config[$key] = (string) $this->_config[$key];
				break;
				case (self::DATA_TYPE_INT):
					$this->_config[$key] = (int) $this->_config[$key];
				break;
				case (self::DATA_TYPE_BOOLEAN):
					$this->_config[$key] = (bool) $this->_config[$key];
				break;
				case (self::DATA_TYPE_FLOAT):
				case (self::DATA_TYPE_DOUBLE):
					$this->_config[$key] = (float) $this->_config[$key];
				break;
			}
		}

		return $this;
	}

	public function get_prepared_data()
	{
		// formats data for data provider
		$array = array();
		foreach($this->config() as $key => $value) {
			$array []= array('key' => $key, 'value' => $value);
		}


		return $array;
	}

	public function as_array()
	{
		return $this->config();
	}
}