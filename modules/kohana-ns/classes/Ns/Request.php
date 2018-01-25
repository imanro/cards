<?php

class Ns_Request extends Kohana_Request {

	/**
	 * Sets and gets the controller for the matched route.
	 *
	 * @param   string   $controller  Controller to execute the action
	 * @return  mixed
	 */
	public function controller_name()
	{
			$last_namespace_position = strripos($this->_controller, '\\');
			return ucfirst(substr($this->_controller, $last_namespace_position + 1));
	}
}