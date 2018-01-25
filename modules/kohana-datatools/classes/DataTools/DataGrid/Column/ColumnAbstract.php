<?php

abstract class DataTools_DataGrid_Column_ColumnAbstract {

	protected $_config = array();

	/**
	 * @var DataGrid
	 */
	protected $_data_grid;

	public static function factory($name, $config = array())
	{
		return new $name($config);
	}

	public function __construct($config = array())
	{
		if(isset($config['data_grid'])){
			$this->data_grid($config['data_grid']);
			unset($config['data_grid']);
		}

		$this->_config = Arr::merge($this->_config, $config);
	}

	public function header()
	{
		return '<th>' . $this->header_content() . '</th>';
	}

	abstract public function header_content();

	public function data($row, $key)
	{
		return '<td>' . $this->data_content($row, $key) . '</td>';
	}

	abstract public function data_content($row, $key);

	public function data_grid(DataGrid $data_grid = NULL)
	{
		if (!is_null($data_grid))
		{
			$this->_data_grid = $data_grid;
			return $this;
		}
		else
		{
			return $this->_data_grid;
		}
	}

	public function config($key = NULL, $value = NULL)
	{
		if (is_null($key))
		{
			return $this->_config;
		}
		elseif (is_null($value))
		{
			return $this->_config[$key];
		}
		else
		{
			$this->_config[$key] = $value;
			return $this;
		}
	}
}