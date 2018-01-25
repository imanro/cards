<?php

/**
 * @author manro
 * Helper to use with formo
 */
class Form_FormAbstract {

	protected $_formo_alias = '';

	public static function factory($name, $id = null, $options = NULL)
	{
		$object = new $name($id);
		if(!is_null($options)) {
			foreach($options as $key => $value){
				$object->set($key, $value);
			}
		}
		return $object;
	}

	/**
	 * @param array $fields (not used in non-ORM forms, just for signature compatibility)
	 * @param Formo $form
	 * @return Formo
	 */
	public function get_form(array $fields = NULL, Formo $form = NULL)
	{
		if(is_null($form)){
			$alias = $this->_formo_alias;
			$form = Formo::form(['alias' => $alias]);
		}

		$this->formo($form);
		return $form;
	}

	protected function formo(Formo $form)
	{
		return $form;
	}
}