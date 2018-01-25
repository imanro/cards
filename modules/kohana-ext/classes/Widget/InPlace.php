<?php

class Widget_InPlace extends WidgetAbstract {

	public $input_tag = 'input';

	public $input_attributes = array();

	public $url = NULL;

	public $control = 'edit';

	public $content;

	public $tag = 'span';

	public $ajax_settings = array();

	public $value = NULL;

	public $js_callbacks = array(
		'show-control' => '',
		'hide-control' => '',
		'start-edit' => '',
		'cancel-edit' => '',
		'save' => '',
	);

	public $data = array();

	public $attributes = array();

	public function read_config(){}

	public function toString()
	{
		// rendering content in tag with additional data-* attributes for in-place editor
		$input_attributes = array();
		foreach($this->input_attributes as $key => $value){
			if(strpos($key, 'data-input-attribute-') === FALSE){
				$key = 'data-input-attribute-' . $key;
			}

			if(!is_scalar($value)){
				$value = json_encode($value);
			}
			$input_attributes[$key] = $value;
		}

		$input_attributes['data-input-tag'] = $this->input_tag;
		$input_attributes['data-url'] = $this->url;

		if (count($this->ajax_settings))
		{
			$input_attributes['data-ajax-settings'] = json_encode($this->ajax_settings);
		}

		if (count($this->data))
		{
			$input_attributes['data-data'] = json_encode($this->data);
		}

		$input_attributes['data-control'] = $this->control;

		foreach($this->js_callbacks as $key => $value){
			if(strlen($value) > 0){
				$input_attributes['data-js-callback-' . $key] = $value;
			}
		}

		if(!is_null($this->value)){
			$input_attributes['data-value'] = $this->value;
		}

		return HTML::tag($this->tag, $this->content, Arr::merge($this->attributes, $input_attributes));
	}
}