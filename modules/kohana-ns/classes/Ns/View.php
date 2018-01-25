<?php

class Ns_View extends Kohana_View {

	/**
	 * Returns a new View object. If you do not define the "file" parameter,
	 * you must call [View::set_filename].
	 *
	 *     $view = View::factory($file);
	 *
	 * @param   string  $file   view filename
	 * @param   array   $data   array of values
	 * @param   string  $force_module only module to search file in
	 * @return  View
	 */
	public static function factory($file = NULL, array $data = NULL, $force_module = NULL)
	{
		if(!is_null($force_module)){
			$force_module = strtolower($force_module);
		}
		return new self($file, $data, $force_module);
	}

	/**
	 * Sets the initial view filename and local data. Views should almost
	 * always only be created using [View::factory].
	 *
	 *     $view = new View($file);
	 *
	 * @param   string  $file   view filename
	 * @param   array   $data   array of values
	 * @param   string  $force_module only module to search file in
	 * @uses    View::set_filename
	 */
	public function __construct($file = NULL, array $data = NULL, $force_module = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file, $force_module);
		}

		if ($data !== NULL)
		{
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}
	}

	/**
	 * Sets the view filename.
	 *
	 *     $view->set_filename($file);
	 *
	 * @param   string  $file   view filename
	 * @param   string  $force_module only module to search file in
	 * @return  View
	 * @throws  View_Exception
	 */
	public function set_filename($file, $force_module = NULL)
	{
	if (($path = Kohana::find_file_module('views', $file, NULL, FALSE, $force_module)) === FALSE)
		{
			throw new View_Exception('The requested view :file could not be found', array(
				':file' => $file,
			));
		}

		// Store the file path locally
		$this->_file = $path;

		return $this;
	}
}