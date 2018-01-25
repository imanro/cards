<?php

trait Config_Object_Helper_Properties {

	public function assign_config(array $config)
	{
		$this->_check_config_object_helper();
		// just assign all config (as properties of class)
		$this->config($config);
	}

	/**
	 * Get/Set whole conifg or value
	 */
	public function config($name = NULL, $value = NULL)
	{
		$this->_check_config_object_helper();

		if (is_null($value))
		{
			// setter for all
			if (is_null($name))
			{
				return get_object_vars($this);
			}
			else
			{
				if (is_array($name))
				{
					foreach ($name as $key => $value)
					{
						if(!property_exists($this, $key)){
							throw new Exception(vsprintf('"%s" property is not exists, unable assign it through "%s" helper', array($key, 'Config_Object_Helper_Properties')));
						}
						if (is_array($this->$key))
						{
							$this->$key = array_merge($this->$key, $value);
						}
						else
						{
							$this->$key = $value;
						}
					}
					return $this;
				}
			}
		}
		else
		{
			// setter for value
			$this->$name = $value;
			return $this;
		}
	}

	private function _check_config_object_helper()
	{
		if (!$this instanceof Config_Object_Client_Interface)
		{
			throw new Exception(vsprintf('Class using %s must implement %s', array(
				Config_Object_Helper_Properties::class,
				Config_Object_Client_Interface::class
			)));
		}
	}
}