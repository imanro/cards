<?php

interface Config_Object_Client_Interface {

	/**
	 * Must return array of configuration for component from file
	 */
	public function read_config();
}