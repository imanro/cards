<?php

namespace Common\Model;

use Kohana;
use ORM;

class Page extends ORM {

	protected $_table_name = 'page';

	protected $_table_columns = array(
		'id' => null,
		'title' => null,
		'text' => null,
		'slug' => null,
		'is_hidden' => null,
		'create_time' => null
	);

	public function rules()
	{
		return array(
		'title' => array(
				array('not_empty'),
				array('max_length', array(':value', '255')),
			),
			'slug' => array(
				array('not_empty'),
				array('max_length', array(':value', '100')),
			),
		);
	}

	public function labels()
	{
		// non-module-specific messages, allowed to override by next modules
		return Kohana::message('labels/model/page');
	}
}