<?php

namespace Common\Model\Mail\Settings;

use Kohana;
use ReflectionClass;
use ReflectionProperty;

 class Template {

	 public $smtp_host;

	 public $smtp_port;

	 public $smtp_start_tls;

	 public $smtp_tls_ssl;

	 protected $_loaded;

	 public static function factory($name = NULL, $settings = NULL)
	 {
		 if (!is_null($name))
		 {
			 // reading config
			 $config = Kohana::$config->load('mail_settings_template');

			 if (isset($config[$name]))
			 {
				 $settings = $config[$name];
			 }
			 else
			 {
				 throw new \Exception(vsprintf('Unknown template name "%s"', array(
					 $name
				 )));
			 }
		 }
		 // creating model
		 $model = new self();

		 if (!is_null($settings))
		 {
			 // assign properties from config
			 $model->load($settings);
		 }

		 return $model;
	 }

	 public static function load_all()
	 {
		 $config = Kohana::$config->load('mail_settings_template');

		 $pool = array();
		 foreach ($config as $key => $values)
		 {
			 $pool[$key] = self::factory(NULL, $values);
		 }

		 return $pool;
	 }

	 public function load($array)
	 {
		 $this->populate($array);
		 $this->loaded(TRUE);
		 return $this;
	 }

	 public function populate($array)
	 {
		 $class = get_class($this);
		 foreach ($array as $key => $value)
		 {
			 if (property_exists($class, $key))
			 {
				 $this->$key = $value;
			 }
			 else
			 {
				 throw new \Exception(vsprintf('The "%s" property does not exists in "%s"', array(
					$key,
					$class
				)));
			}
		}
		return $this;
	}

	public function loaded($value = NULL)
	{
		if (!is_null($value))
		{
			$this->_loaded = $value;
			return $this;
		}
		else
		{
			return $this->_loaded;
		}
	}

	public function get_form_options_template()
	{
		$config = Kohana::$config->load('mail_settings_template');

		$array = array();
		foreach ($config as $key => $value)
		{
			$array[$key] = $key;
		}

		return $array;
	}

	public function as_array()
	{
		$reflect = new ReflectionClass($this);
		$props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

		$object = array();
		foreach ($props as $value)
		{
			$name = $value->name;
			$object[$name] = $this->$name;
		}

		return $object;
	}
}