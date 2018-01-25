<?php

class Route extends Ns_Route {

	/**
	 * Generates a URI for the current route based on the parameters given.
	 * This version puts extra params, that\'s not found in route, in query string
	 *
	 *     // Using the "default" route: "users/profile/10"
	 *     $route->uri(array(
	 *         'controller' => 'users',
	 *         'action'     => 'profile',
	 *         'id'         => '10'
	 *     ));
	 *
	 * @param   array   $params URI parameters
	 * @return  string
	 * @throws  Kohana_Exception
	 * @uses    Route::REGEX_GROUP
	 * @uses    Route::REGEX_KEY
	 */
	public function uri(array $params = NULL)
	{
		if ($params)
		{
			// @issue #4079 rawurlencode parameters
			$params = array_map('rawurlencode', $params);
			// decode slashes back, see Apache docs about AllowEncodedSlashes and AcceptPathInfo
			$params = str_replace(array('%2F', '%5C'), array('/', '\\'), $params);
		}

		$defaults = $this->_defaults;

		/**
		 * Recursively compiles a portion of a URI specification by replacing
		 * the specified parameters and any optional parameters that are needed.
		 *
		 * @param   string  $portion    Part of the URI specification
		 * @param   boolean $required   Whether or not parameters are required (initially)
		 * @return  array   Tuple of the compiled portion and whether or not it contained specified parameters
		 */
		$compile = function ($portion, $required) use (&$compile, $defaults, $params)
		{
			$missing = array();

			$used = array();

			$pattern = '#(?:'.Route::REGEX_KEY.'|'.Route::REGEX_GROUP.')#';
			$result = preg_replace_callback($pattern, function ($matches) use (&$compile, $defaults, &$missing, $params, &$required, &$used)
			{
				if ($matches[0][0] === '<')
				{
					// Parameter, unwrapped
					$param = $matches[1];

					if (isset($params[$param]))
					{
						// This portion is required when a specified
						// parameter does not match the default
						$required = ($required OR ! isset($defaults[$param]) OR $params[$param] !== $defaults[$param]);

						$used[$param] = true;

						// Add specified parameter to this result
						return $params[$param];
					}

					// Add default parameter to this result
					if (isset($defaults[$param]))
						return $defaults[$param];

					// This portion is missing a parameter
					$missing[] = $param;
				}
				else
				{
					// Group, unwrapped

					$result = $compile($matches[2], FALSE);

					if(isset($result[2])){
						$used = \Arr::merge($used, $result[2]);
					}

					if ($result[1])
					{
						// This portion is required when it contains a group
						// that is required
						$required = TRUE;

						// Add required groups to this result
						return $result[0];
					}

					// Do not add optional groups to this result
				}
			}, $portion);

			if ($required AND $missing)
			{
				throw new Kohana_Exception(
					'Required route parameter not passed: :param',
					array(':param' => reset($missing))
				);
			}

			return array($result, $required, $used);
		};

		list($uri, $required, $used) = $compile($this->_uri, TRUE);

		$query_params = array_diff_key($params, $used);

		if($query_params){
			$uri .= URL::query($query_params);
		}

		// Trim all extra slashes from the URI
		$uri = preg_replace('#//+#', '/', rtrim($uri, '/'));

		if ($this->is_external())
		{
			// Need to add the host to the URI
			$host = $this->_defaults['host'];

			if (strpos($host, '://') === FALSE)
			{
				// Use the default defined protocol
				$host = Route::$default_protocol.$host;
			}

			// Clean up the host and prepend it to the URI
			$uri = rtrim($host, '/').'/'.$uri;
		}

		return $uri;
	}
}