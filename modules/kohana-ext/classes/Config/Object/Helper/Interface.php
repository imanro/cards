<?php

interface Config_Object_Helper_Interface {
	public function  assign_config(array $config);

	public function config($name = NULL, $value = NULL);
}