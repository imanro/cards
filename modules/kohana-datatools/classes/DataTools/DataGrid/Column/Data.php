<?php

class DataTools_DataGrid_Column_Data extends DataTools_DataGrid_Column_ColumnAbstract {
	protected $_config = array(
		'label' => NULL,
		'attribute' => NULL,
		'content' => NULL,
	);

	public function header()
	{
		return '<th' . HTML::attributes(array('class' => $this->_config['attribute'])). '>' . $this->header_content() . '</th>';
	}

	public function header_content()
	{
		if(!empty($this->_config['label'])){
			return $this->_config['label'];
		} else if(!empty($this->_config['attribute'])){
			// take model
			$data_source = $this->data_grid()->data_provider()->data_source();

			if($data_source && method_exists($data_source, 'label')) {
				return $data_source->label($this->_config['attribute']);
			} else {
				return '';
			}
		} else {
			return '';
		}
	}

	public function data($row, $key)
	{
		return '<td' . HTML::attributes(array('class' => $this->_config['attribute'])). '>' . $this->data_content($row, $key) . '</td>';
	}

	public function data_content($row, $key)
	{
		if (is_callable($this->_config['content']))
		{
			if(!isset($this->_config['attribute'])){
				throw new Kohana_Exception(vsprintf('"%s" param is mandatory for "%s" config', array('attribute', __CLASS__)));
			}

			return call_user_func($this->_config['content'], $row, $this->_config['attribute'], $key, $this);
		}
		else
		{
			if (!empty($this->_config['attribute']))
			{
				if (is_object($row) && method_exists($row, 'get'))
				{
					return HTML::chars(Text::limit_chars($row->get($this->_config['attribute']), 64, '...'));
				}
				else
				{
					return HTML::chars(Text::limit_chars($row[$this->_config['attribute']], 64, '...'));
				}
			}
		}
	}
}