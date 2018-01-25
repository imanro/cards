<?php

class Validation extends Kohana_Validation {
		/**
	 * Returns the error messages. If no file is specified, the error message
	 * will be the name of the rule that failed. When a file is specified, the
	 * message will be loaded from "field/rule", or if no rule-specific message
	 * exists, "field/default" will be used. If neither is set, the returned
	 * message will be "file/field/rule".
	 *
	 * By default all messages are translated using the default language.
	 * A string can be used as the second parameter to specified the language
	 * that the message was written in.
	 *
	 *     // Get errors from messages/forms/login.php
	 *     $errors = $Validation->errors('forms/login');
	 *
	 * @uses    Kohana::message
	 * @param   string  $file       file to load error messages from
	 * @param   mixed   $translate  translate the message
	 * @return  array
	 */
	public function errors($file = NULL, $translate = TRUE, $force_module = NULL)
	{
		if ($file === NULL)
		{
			// Return the error list
			return $this->_errors;
		}

		if(!is_null($force_module)) {
			$force_module = strtolower($force_module);
		}

		// Create a new message list
		$messages = array();

		foreach ($this->_errors as $field => $set)
		{
			list($error, $params) = $set;

			// Get the label for this field
			$label = $this->_labels[$field];

			if ($translate)
			{
				if (is_string($translate))
				{
					// Translate the label using the specified language
					$label = __($label, NULL, $translate);
				}
				else
				{
					// Translate the label
					$label = __($label);
				}
			}

			// Start the translation values list
			$values = array(
				':field' => $label,
				':value' => Arr::get($this, $field),
			);

			if (is_array($values[':value']))
			{
				// All values must be strings
				$values[':value'] = implode(', ', Arr::flatten($values[':value']));
			}

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

					// Check if a label for this parameter exists
					if (isset($this->_labels[$value]))
					{
						// Use the label as the value, eg: related field name for "matches"
						$value = $this->_labels[$value];

						if ($translate)
						{
							if (is_string($translate))
							{
								// Translate the value using the specified language
								$value = __($value, NULL, $translate);
							}
							else
							{
								// Translate the value
								$value = __($value);
							}
						}
					}

					// Add each parameter as a numbered value, starting from 1
					$values[':param'.($key + 1)] = $value;
				}
			}

			if ($message = Kohana::message($file, "{$field}.{$error}", NULL, $force_module) AND is_string($message))
			{
				// Found a message for this field and error
			}
			elseif ($message = Kohana::message($file, "{$field}.default", NULL, $force_module) AND is_string($message))
			{
				// Found a default message for this field
			}
			elseif ($message = Kohana::message($file, $error, NULL, $force_module) AND is_string($message))
			{
				// Found a default message for this error
			}
			elseif ($message = Kohana::message('validation', $error, NULL, $force_module) AND is_string($message))
			{
				// Found a default message for this error
			}
			else
			{
				// No message exists, display the path expected
				$message = "{$file}.{$field}.{$error}";
			}

			if ($translate)
			{
				if (is_string($translate))
				{
					// Translate the message using specified language
					$message = __($message, $values, $translate);
				}
				else
				{
					// Translate the message using the default language
					$message = __($message, $values);
				}
			}
			else
			{
				// Do not translate, just replace the values
				$message = strtr($message, $values);
			}

			// Set the message for this field
			$messages[$field] = $message;
		}

		return $messages;
	}
}