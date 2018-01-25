<?php

class Formo extends Formo_Core_Formo {

	public function remove_attr($key)
	{
		unset($this->_attr[$key]);
		return $this;
	}

	public function add_rule($rule, $params = NULL)
	{
		if (is_scalar($rule[0]) && $rule[0] == 'empty_rules')
		{
			$this->empty_rules();
		}
		else
		{
			if (is_array($rule))
			{
				$this->_add_rule($rule);
			}
			else
			{
				if (isset($params))
				{
					$this->_add_rule(array(
						$rule,
						$params
					));
				}
				else
				{
					$this->_add_rule(array(
						$rule
					));
				}
			}

			if (is_scalar($rule[0]) && $rule[0] == 'not_empty')
			{
				$this->attr('required', 'true');
			}
		}
		return $this;

	}

	public function empty_rules($field_name = NULL)
	{
		if (is_null($field_name))
		{
			$this->set('rules', array());
			$this->remove_attr('required');
		}
		else
		{
			foreach ($this->get('fields') as $field)
			{
				if ($field->get('alias') == $field_name)
				{
					$field->set('rules', array());
					$field->remove_attr('required');
				}
			}
		}
	}
}