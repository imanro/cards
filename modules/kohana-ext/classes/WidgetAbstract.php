<?php

abstract class WidgetAbstract implements Config_Object_Client_Interface, Config_Object_Helper_Interface {

	use Config_Object_Helper_Properties;

	public function __construct($config)
	{
		$this->assign_config($config);
	}

	public function __toString()
	{
		return $this->toString();
	}
}