<?php

class DataTools_DataProvider {

	protected $_data;

	/**
	 * @var ORM|array
	 */
	protected $_data_source;

	protected $_primary_key;

	/**
	 * @var Pagination
	 */
	protected $_pagination;

	protected $_count_all = 0;

	protected $_count = 0;

	public function __construct($config = array())
	{
		if (isset($config['data_source']))
		{
			$this->data_source($config['data_source']);
		}
	}

	public function reset()
	{
		$this->_data = NULL;
		$this->_data_source = NULL;
		$this->_primary_key = NULL;
		$this->_count_all = 0;
		$this->_count = 0;
	}

	public function prepare_data()
	{
		$data_source = $this->data_source();

		if (is_null($data_source))
		{
			throw new Kohana_Exception('Data source has not been set yet');
		}
		else
		{
			if ($data_source instanceof ORM)
			{
				$this->_prepare_data_orm($data_source);
			}
			else if (is_array($data_source))
			{
				$this->_prepare_data_array($data_source);
			}
			else
			{
				throw new Kohana_Exception('Unknown data source class');
			}
		}
	}

	public function data_source($data_source = NULL)
	{
		if (is_null($data_source))
		{
			return $this->_data_source;
		}
		else
		{
			$this->_data_source = $data_source;
			return $this;
		}
	}

	public function data($data = NULL)
	{
		if (is_null($data))
		{
			if (is_null($this->_data))
			{
				$this->prepare_data();
			}
			return $this->_data;
		}
		else
		{
			$this->_data = $data;
			return $this;
		}
	}

	public function primary_key($key = NULL)
	{
		if (is_null($key))
		{
			return $this->_primary_key;
		}
		else
		{
			$this->_primary_key = $key;
			return $this;
		}
	}

	public function count_all($count = NULL)
	{
		if (is_null($count))
		{
			return $this->_count_all;
		}
		else
		{
			$this->_count_all = $count;

			// notify paginator about this count
			$pagination = $this->pagination();

			if($pagination) {
				$config = $pagination->config_group();
				$config['total_items'] = $this->count_all();
				$pagination->setup($config);
			}

			return $this;
		}
	}

	public function count($count = NULL)
	{
		if (is_null($count))
		{
			return $this->_count;
		}
		else
		{
			$this->_count = $count;
			return $this;
		}
	}

	public function pagination(Pagination $pagination = NULL)
	{
		if (is_null($pagination))
		{
			if(is_null($this->_pagination)){
				$this->_pagination = $this->_init_pagination();
			}

			return $this->_pagination;
		}
		else
		{
			$this->_pagination = $pagination;

			return $this;
		}
	}

	public function get_key($row)
	{
		$pk = $this->primary_key();

		if ($row instanceof ORM && $pk)
		{
			return $row->$pk;
		}
		else if (is_array($row) && $pk)
		{
			return $row[$pk];
		}
		else
		{
			return null;
		}
	}

	protected function _prepare_data_orm(ORM $data_source)
	{
		// count all
		$count_model = clone $data_source;
		$this->count_all($count_model->count_all());

		// primary_key
		$this->primary_key($data_source->primary_key());

		// limit & offset
		$pagination = $this->pagination();
		$per_page = $pagination->items_per_page;
		$current_page = $pagination->current_page ?: 1;
		$data_source->limit($per_page);
		$data_source->offset($per_page * ($current_page - 1));

		// order by

		// TODO

		// data
		$this->data($data_source->find_all());

		// count on this page
		$this->count(count($this->data()));
	}


	protected function _prepare_data_array(array $data_source)
	{
		$this->count_all(count($data_source));

		$this->primary_key(NULL);

		$this->data($data_source);

		$this->count(count($this->data()));
	}

	protected function _init_pagination()
	{
		return Pagination::factory();
	}
}