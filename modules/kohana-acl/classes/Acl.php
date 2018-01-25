<?php

namespace Acl;

class Acl {

	/**
	 * @param string $type
	 * @return Acl
	 */
	public static function factory($type = 'Dac')
	{
		$class = __NAMESPACE__ . '\\' . $type;
		return new $class();
	}

	public function has_access($permission, $user, $entity)
	{
		return false;
	}
}