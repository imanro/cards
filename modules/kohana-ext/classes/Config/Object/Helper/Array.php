<?php

trait Config_Object_Helper_Array {

	public function assign_config(array $config)
	{
		$this->_check_config_object_helper();
		// default config <- file config <- parameter config
		$this->config(Arr::merge($this->_config, Arr::merge((array) $this->read_config(), $config)));
	}

	/**
	 * Get/Set whole conifg or value
	 */
	public function config($name = NULL, $value = NULL)
	{

		$this->_check_config_object_helper();
		if (is_null($value))
		{
			if (is_null($name))
			{
				// getter for all
				return $this->_config;
			}
			else
			{
				if (is_array($name))
				{
					// setter for all
					$this->_config = $name;
					return $this;
				}
				else
				{
					// getter for value
					return $this->_config[$name];
				}
			}
		}
		else
		{
			// setter for value
			$this->_config[$name] = $value;
			return $this;
		}
	}

	private function _check_config_object_helper()
	{
		if (!$this instanceof Config_Object_Client_Interface)
		{
			throw new Exception(vsprintf('Class using %s must implement %s', array(
				Config_Object_Helper_Array::class,
				Config_Object_Client_Interface::class
			)));
		}

		if (!property_exists(get_class($this), '_config'))
		{
			throw new Exception(vsprintf('Class using %s must have %s property', array(
				Config_Object_Helper_Array::class,
				'_config'
			)));
		}
	}
}