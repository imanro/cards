<?php

class Form extends Kohana_Form {

	public static function label_formo(Formo $formo, $name, $options = array())
	{
		if(!isset($formo->$name)){
			throw new Kohana_Exception('Property :name is not exists in form', array(':name' => $name));
		}

		$classes_add = array();
		if($formo->$name->attr('required')){
			$classes_add []= 'required';
		}

		if(isset($options['class'])) {
			$options['class'] .= ((count($classes_add))? ' ' . implode(' ', $classes_add) : '');
		} else {
			$options['class'] = implode(' ', $classes_add);
		}

		$id = implode('-', array($formo->get('alias'), $formo->$name->get('alias')));

		return self::label($id, $formo->$name->label(), $options);
	}

	public static function input_formo(Formo $formo, $name, $options = array())
	{
		if(!isset($formo->$name)){
			throw new Kohana_Exception('Property :name is not exists in form', array(':name' => $name));
		}

		if(!isset($options['id'])){
			$options['id'] = implode('-', array($formo->get('alias'), $formo->$name->get('alias')));
		}

		if(!empty($options['required']) && ($options['required'] == 'false' || !$options['required'])) {
			unset($options['required']);
			$formo->$name->remove_attr('required');
		}

		//var_dump($formo->$name->attr('required'));
		//var_dump($formo->$name->attr($options)->input());

		//$options = Arr::merge($options, self::_rules_to_attr($formo->$name->get('rules')));
		return $formo->$name->attr($options)->input();
	}

	public static function error_formo($formo, $name, $options = array())
	{
		if(!isset($formo->$name)){
			throw new Kohana_Exception('Property :name is not exists in form', array(':name' => $name));
		}

		if($error = $formo->$name->error()) {
			$tag = isset($options['tag']) ? $options['tag'] : 'div';
			unset($options['tag']);
			return HTML::tag($tag, $error, $options);
		}
	}

	public static function class_toggle($name, $condition)
	{
		return $condition ? $name : '';
	}

	protected static function _rules_to_attr($array)
	{
		$options = array();

		foreach($array as $row) {
			if(!is_array($row) && empty($row[0])){
				continue;
			}

			$name = $row[0];

			switch($name) {
				default:
					continue;
					break;
				case('max_length'):
					$length = $row[1][1];
					$options['maxlength'] = $length;
					break;
			}
		}

		return $options;
	}
}