<?php

class DataTools_DataGrid_Column_Action extends DataTools_DataGrid_Column_ColumnAbstract {

	protected $_config = array(
		'template' => '{delete} {update}',
		'buttons' => NULL,
		'message_file' => 'view',
		'route_name' => 'default',
		'route_params' => array(),
		'id_param' => 'id',
		'actions_map' => array(),
		'content' => NULL,
	);

	protected $_buttons;

	public function header()
	{
		return '<th ' . HTML::attributes(array('class' => 'column-action')) . '></th>';
	}

	public function header_content()
	{
	}

	public function data_content($row, $key)
	{
		if (is_callable($this->_config['content']))
		{
			return call_user_func($this->_config['content'], $row, $key, $this);
		}
		else
		{
			return $this->data_content_default($row, $key);
		}
	}

	public function data_content_default($row, $key)
	{
		$buttons = $this->buttons();

		return preg_replace_callback('/\{(.+?)\}/', function ($matches) use ($row, $key, $buttons)
		{
			$name = $matches[1];
			if (isset($buttons[$name]))
			{
				$action = isset($this->_config['actions_map'][$name]) ? $this->_config['actions_map'][$name] : $name;
				$url = $this->_get_url($action, $row, $key);

				return call_user_func($buttons[$name], $url, $row, $key);
			}
			else
			{
				return '';
			}
		}, $this->_config['template']);
	}

	public function buttons($config = NULL)
	{
		if(!is_null($config))
		{
			// setter
			$this->_config['buttons'] = $config;
			// re-init
			unset($this->_buttons);
		}
		else
		{
			// getter
			if(is_null($this->_buttons))
			{
				$this->_buttons = array();

				$this->_buttons['update'] = function($url, $model, $key)
				{
					$options = array(
						'title' => Kohana::message($this->_config['message_file'], 'label.update', 'Update')
					);
					return HTML::link($url, '<span class="glyphicon glyphicon-pencil"></span>', $options);
				};

				$this->_buttons['delete'] = function($url, $model, $key)
				{
					$options = array(
						'title' => Kohana::message($this->_config['message_file'], 'label.delete', 'Delete'),
						'onclick' => 'return confirm("' . Kohana::message($this->_config['message_file'], 'message.confirm_delete', 'Are you sure you want\'s to delete this?') . '");'
					);
					return HTML::link($url, '<span class="glyphicon glyphicon-trash"></span>', $options);
				};
			}

			return $this->_buttons;
		}
	}

	protected function _get_url($action, $model, $key)
	{
		$route_params = Arr::merge($this->_config['route_params'], array($this->_config['id_param'] => $key, 'action' => $action));
		return Route::get($this->_config['route_name'])->uri($route_params);
	}
}