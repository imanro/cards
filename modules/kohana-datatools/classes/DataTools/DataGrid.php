<?php

class DataTools_DataGrid /* implements Config_Object_Client_Interface, Config_Object_Helper_Interface */ {

	/* use Config_Object_Helper_Array; */

	protected $_config = array(
		// pagination settings
		'pagination' => array(),
		'columns' => array(),
		'items_options' => array('class' => 'table data-grid'),
		'route_name' => 'default',
		'route_params' => array(),
		'default_column_class' => 'DataGrid_Column_Data',
		'after_get_data' => NULL,
		'rows_attributes' => array(),
	);

	/**
	 * @var DataProvider
	 */
	protected $_data_provider;

	protected $_columns;

	protected $_data;

	public static function factory($config = array())
	{
		return new DataGrid($config);
	}

	public function __construct($config = array())
	{
		if (isset($config['data_provider']))
		{
			$this->data_provider($config['data_provider']);
			unset($config['data_provider']);
		}

		if (isset($config['data_source']))
		{
			$this->data_source($config['data_source']);
			unset($config['data_source']);
		}

		$this->assign_config($config);
	}

	public function init()
	{
		$this->_init_columns();
	}

	public function assign_config(array $config)
	{
		$this->config(Arr::merge($this->_config, Arr::merge((array) $this->read_config(), $config)));
	}

	public function read_config()
	{
		$config_file = Kohana::$config->load('datagrid');
		return $config_file;
	}

	public function config($name = NULL, $value = NULL)
	{
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

	public function columns($config = NULL)
	{
		if (!is_null($config))
		{
			// setter
			$this->_config['columns'] = $config;
			return $this;
		}
		else
		{
			// getter
			return $this->_config['columns'];
		}
	}

	public function add_column($config, $position = NULL)
	{
		if (is_null($position))
		{
			$this->_config['columns'][] = $config;
		}
		else
		{
			$new_config = array();
			$counter = 1;
			$added = FALSE;

			foreach ($this->_config['columns'] as $row)
			{
				if ($counter == $position)
				{
					$new_config[] = $config;
					$added = TRUE;
				}
				$new_config[] = $row;
				$counter++;
			}

			if (!$added)
			{
				$new_config[] = $config;
			}

			$this->_config['columns'] = $new_config;
		}
	}

	public function data_source($source = NULL)
	{
		if (is_null($source))
		{
			return $this->data_provider()->data_source();
		}
		else
		{
			$data_provider = new DataProvider(array(
				'data_source' => $source
			));
			$this->data_provider($data_provider);
			return $this;
		}
	}

	/**
	 *
	 * @param DataProvider $provider
	 * @throws Kohana_Exception
	 * @return DataTools_DataGrid|DataProvider
	 */
	public function data_provider($provider = NULL)
	{
		if(!is_null($provider))
		{
			$this->_data_provider = $provider;
			return $this;
		}
		else
		{
			if(is_null($this->_data_provider)){
				throw new Kohana_Exception('DataProvider has not been set yet');
			}
			else if(!$this->_data_provider instanceof DataProvider)
			{
				throw new Kohana_Exception('Unknown DataProvider class: must be instance of DataProvider');
			}
			return $this->_data_provider;
		}
	}

	protected function _init_columns()
	{
		$this->_columns = array();

		foreach ($this->_config['columns'] as $column)
		{
			$column = $this->_create_column($column);
			$this->_columns []= $column;
		}
	}

	public function render_header()
	{
		$content = array();

		$content []= '<thead>';

		foreach($this->_columns as $column) {
			$content []= $column->header();
		}

		$content []= '</thead>';

		return implode("\n", $content);
	}

	public function render_body()
	{
		$this->_get_data();
		$data = $this->data();

		$content = array();
		$content[] = '<tbody>';

		foreach ($data as $row)
		{
			$key = $this->data_provider()->get_key($row);

			$content []= '<tr'.
				HTML::attributes($this->row_attributes($key)).
			'>';
			foreach ($this->_columns as $column)
			{
				$content[] = $column->data($row, $key);
			}
			$content []= '</tr>';
		}

		$content []= '</tbody>';

		return implode("\n", $content);
	}

	public function render_items()
	{
		$header = $this->render_header();
		$body = $this->render_body();
		$content = array_filter(
			array(
				$header,
				$body
			)
		);

		return '<table ' . HTML::attributes($this->_config['items_options']) . '>' .
			implode("\n", $content) .
			'</table>';
	}

	public function render_pagination()
	{
		return $this->data_provider()->pagination();
	}

	public function render()
	{
		$this->init();
		$content = array_filter(array(
			$this->render_items(),
			$this->render_pagination(),
		));

		return implode("\n", $content);
	}

	protected function _after_get_data()
	{
		if(!is_null($this->_config['after_get_data'])){
			call_user_func_array($this->_config['after_get_data'], array($this));
		}
	}

	protected function _create_column($config)
	{
		if (!is_array($config))
		{
			$class = $this->_config['default_column_class'];
			$config = array('attribute' => $config);
		}
		else
		{
			if (isset($config['class']))
			{
				$class = $config['class'];
				unset($config['class']);
			}
			else
			{
				$class = $this->_config['default_column_class'];
			}
		}

		$extracted_config = array_intersect_key($this->_config, array('route_name' => TRUE, 'route_params' => TRUE));
		$config = Arr::merge($extracted_config, $config);

		$config['data_grid'] = $this;
		return DataGrid_Column_ColumnAbstract::factory($class, $config);
	}

	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch(Exception $e)
		{
			Kohana_Exception::handler($e);
			return '';
		}
	}

	protected function _get_data()
	{
		$data_provider = $this->data_provider();
		$data_provider->pagination($this->_init_pagination());

		// count all rows
		$data_provider->prepare_data();

		$this->data($data_provider->data());
		$this->_after_get_data();

		return $this;
	}

	public function data($data = NULL)
	{
		if (!is_null($data))
		{
			$this->_data = $data;
		}
		else
		{
			return $this->_data;
		}
	}

	public function rows_attributes($attributes = NULL)
	{
		if (!is_null($attributes))
		{
			$this->_config['rows_attributes'] = $attributes;
			return $this;
		}
		else
		{
			return $this->_config['rows_attributes'];
		}
	}

	public function row_attributes($key, $attributes = NULL)
	{
		if (!is_null($attributes))
		{
			$this->_config['rows_attributes'][$key] = $attributes;
			return $this;
		}
		else
		{
			if(isset($this->_config['rows_attributes'][$key])) {
				return $this->_config['rows_attributes'][$key];
			} else {
				return array();
			}
		}
	}

	protected function _init_pagination()
	{
		$config = array_intersect_key($this->_config, array('route_name' => TRUE, 'route_params' => TRUE));
		$this->_config['pagination'] = $config;
		$pagination = Pagination::factory($this->_config['pagination']);

		if (!empty($this->_config['pagination']['route_name']))
		{
			$route = Route::get($this->_config['pagination']['route_name']);
		}
		else
		{
			$request = Request::current();
			$route = $request->route();
		}

		$pagination->route($route);
		$pagination->route_params($this->_config['pagination']['route_params']);
		return $pagination;
	}
}