<?php

abstract class Formo_Innards extends Formo_Core_Innards {
	/**
	 * Return the formatted string for a field.
	 *
	 * @access protected
	 * @return string
	 */
	protected function _get_label($label = NULL)
	{
		if($this->config('force_module_labels', TRUE)){
			$force_module = $this->config('module');
		} else {
			$force_module = NULL;
		}

		$label_str = (func_num_args() === 1)
			// If a string was passed as argument, then use it
			? $label
			// Otherwise always use the label
			: $this->driver('get_label');

		$return_str = NULL;

		if ($label_str == NULL)
		{
			return NULL;
		}

		if ($file = $this->config('label_message_file'))
		{
			$parent = $this->parent();

			$prefix = ($parent = $this->parent())
				? $parent->alias()
				: NULL;

			$full_alias = $prefix
				? $prefix.'.'.$label_str
				: $label_str;

			if ($label = Kohana::message($file, $full_alias, NULL, $force_module))
			{
				$return_str = (is_array($label))
					? $full_alias
					: $label;
			}
			elseif($label = Kohana::message($file, $label_str))
			{
				$return_str = $label;
			}
			elseif ($prefix AND ($label = Kohana::message($file, $prefix.'.default', NULL, $force_module)))
			{
				if ($label === ':alias')
				{
					$return_str = $this->alias();
				}
				elseif ($label === ':alias_spaces')
				{
					$return_str = str_replace('_', ' ', $this->alias());
				}
			}
			else
			{
				$return_str = $label_str;
			}
		}
		else
		{
			$return_str = $label_str;
		}

		return ($this->config('translate') === TRUE)
			? __($return_str, NULL)
			: $return_str;
	}

		/**
	 * Convert an error returned from the Validation object to a formatted message
	 *
	 * @access protected
	 * @param array $errors_array (default: NULL)
	 * @return string
	 */
	protected function _error_to_msg( array $errors_array = NULL)
	{

		if($this->config('force_module_validation_messages', FALSE)){
			$force_module = $this->config('module');
		} else {
			$force_module = NULL;
		}

		$file = $this->config('validation_message_file');
		$translate = $this->config('translate', FALSE);
		$errors = ($errors_array !== NULL)
			? $errors_array
			: $this->_errors;

		if ($set = Arr::get($errors, $this->alias()))
		{
			$field = $this->alias();
			list($error, $params) = $set;

			$label = $this->label();
			if ( ! $label)
			{
				if ($title = $this->driver('get_title'))
				{
					$label = $title;
				}
			}

			if ($message = $this->get("error_messages.{$error}"))
			{
				// Found a locally-defined message for this error in this field
			}
			elseif ($file === FALSE)
			{
				// No message found in this field and no external message file
				$message = $error;
			}
			else
			{
				if ($message = Kohana::message($file, "{$field}.{$error}", NULL, $force_module))
				{
					// Found a message for this field and error
				}
				elseif ($message = Kohana::message($file, "{$field}.default", NULL, $force_module))
				{
					// Found a default message for this field
				}
				elseif ($message = Kohana::message($file, $error, NULL, $force_module))
				{
					// Found a default message for this error
				}
				else
				{
					// No message exists, display the path expected
					$message = "{$file}.{$field}.{$error}";
				}

				// Start the translation values list
				$values = array(
					':field' => $label,
					':value' => $this->val(),
				);

				if ($params)
				{
					foreach ($params as $key => $value)
					{
						if (is_array($value))
						{
							// All values must be strings
							$value = implode(', ', Arr::flatten($value));
						}
						elseif (is_object($value))
						{
							// Objects cannot be used in message files
							continue;
						}

						if ($field = $this->parent(TRUE)->find($value, TRUE))
						{
							// Use a field's label if we're referencing a field
							$value = $field->label();
						}

						// Add each parameter as a numbered value, starting from 1
						$values[':param'.($key + 1)] = $value;
					}
				}

				$tr_vals = $values;

				// Fix problem that occurs when :value is an array
				// by creating :value, :value1, :value2, etc. params
				if (is_array(Arr::get($values, ':value')))
				{
					$i = 1;
					foreach ($values[':value'] as $tr_val)
					{
						$key = ($i === 1)
							? ':value'
							: ':value'.$i;

						$tr_vals[$key] = $tr_val;

						$i++;
					}
				}

				// Send the message through strtr
				$message = strtr($message, $tr_vals);
			}

			return ($translate === TRUE)
				? __($message)
				: $message;
		}

		return FALSE;
	}
}