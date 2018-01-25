<?php

class Controller extends Kohana_Controller {

	/**
	 * Executes the given action and calls the [Controller::before] and [Controller::after] methods.
	 *
	 * Can also be used to catch exceptions from actions in a single place.
	 *
	 * 1. Before the controller action is called, the [Controller::before] method
	 * will be called.
	 * 2. Next the controller action will be called.
	 * 3. After the controller action is called, the [Controller::after] method
	 * will be called.
	 *
	 * @throws  HTTP_Exception_404
	 * @return  Response
	 */
	public function execute()
	{
		// Execute the "before" method
		//try {
		$this->before();
		//} catch(Exception $e) {
			//var_dump($action);
			//var_dump($e);
			//exit;
		//}

		$this->execute_action($this->request->action(), FALSE);

		$this->after();

		// Return the response
		return $this->response;
	}

	/**
	 * Special callback runs before _each_ action called by execute_action($action)
	 **/
	public function before_action(){}

	/**
	 * Special callback runs after _each_ action called by execute_action($action)
	 **/
	public function after_action(){}

	public function execute_action($action)
	{
		// substitute action in request (usable if there is "soft" redirect using directly call this method from another)
		$this->request->action($action);

		$this->before_action();

		// Determine the action to use
		// only change: allow dashes in url
		$action = 'action_'.str_replace('-', '_', $action);

		// If the action doesn't exist, it's a 404
		if ( ! method_exists($this, $action))
		{
			throw HTTP_Exception::factory(404,
				'The requested URL :uri was not found on this server.',
				array(':uri' => $this->request->uri())
			)->request($this->request);
		}

		// Execute the action itself
		$this->{$action}();

		// Execute the "after action" method
		$this->after_action();
	}
}