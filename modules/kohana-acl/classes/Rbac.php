<?php

namespace Acl;

use Acl\Rbac\SubjectInterface;
use Acl\Rbac\ResourceInterface;
use Acl\Rbac\RoleInterface;

class Rbac {

	const PERMISSION_WRITE = 2;

	const PERMISSION_READ = 4;

	const PERMISSION_EXEC = 1;

	const TYPE_ALLOW = 2;

	const TYPE_DENY = 3;

	const MAP_VAR_RESOURCES = 'resource_id';

	const MAP_VAR_ROLES = 'role_id';

	const MAP_VAR_PERMISSION = 'permisssion';

	const MAP_VAR_TYPE = 'type';

	protected $_rules = array();

	public function has_access($permission, $subject, $resource)
	{
		if($subject instanceof SubjectInterface) {
		// we have to receive role from subject
			$roles = $subject->get_roles();
		} else if($subject instanceof RoleInterface ) {
			$roles = array($subject);
		} else {
			throw new \Acl\Rbac\Exception(vsprintf('Subject must be an instance of %s or %s interfaces', array(SubjectInterface::class, RoleInterface::class)));
		}

		if($resource instanceof ResourceInterface){
			$resource_id = $resource->get_resource_id();
		} else {
			$resource_id = $resource;
		}

		foreach($roles as $role) {
			/* @var RoleInterface $role */
			if($this->is_allowed($permission, $role->get_role_id(), $resource_id)) {
				return TRUE;
			}
		}

		return FALSE;
	}

	public function is_allowed($permission, $role_id, $resource_id)
	{
		$rules = $this->get_rules($resource_id);

		$allowed = $this->_check_access($rules, self::TYPE_ALLOW, $role_id, $permission);
		$denied = $this->_check_access($rules, self::TYPE_DENY, $role_id, $permission);

		if($denied || !$allowed){
			return FALSE;
		} else {
			return TRUE;
		}
	}

	public function setup_rules($map, $default_permission = self::PERMISSION_EXEC)
	{
		$default = array(
			self::MAP_VAR_RESOURCES => NULL,
			self::MAP_VAR_ROLES => NULL,
			self::MAP_VAR_PERMISSION => $default_permission,
			self::MAP_VAR_TYPE => self::TYPE_ALLOW,
		);

		foreach( $map as $array)
		{
			$array = array_merge($default, $array);

			if(is_null($array[self::MAP_VAR_RESOURCES]) || is_null($array[self::MAP_VAR_ROLES])){
				throw new \Acl\Rbac\Exception('Wrong format for rule map definition: resources or roles not given');
			}
			if (!is_array($array[self::MAP_VAR_RESOURCES]))
			{
				$resources = array(
					$array[self::MAP_VAR_RESOURCES]
				);
			}
			else
			{
				$resources = $array[self::MAP_VAR_RESOURCES];
			}

			if (!is_array($array[self::MAP_VAR_ROLES]))
			{
				$roles = array(
					$array[self::MAP_VAR_ROLES]
				);
			}
			else
			{
				$roles = $array[self::MAP_VAR_ROLES];
			}

			foreach ($resources as $resource)
			{
				foreach ($roles as $role)
				{
					$this->add_rule($resource, $role, $array[self::MAP_VAR_PERMISSION], $array[self::MAP_VAR_TYPE]);
				}
			}
		}

		return $this;
	}

	public function allow($role_id, $resource_id, $permission)
	{
		$this->add_rule($resource_id, $role_id, $permission, self::TYPE_ALLOW);

		return $this;
	}

	public function deny($role_id, $resource_id, $permission)
	{
		$this->add_rule($resource_id, $role_id, $permission, self::TYPE_DENY);

		return $this;
	}

	public function set_rules($resource_id, $rules)
	{
		$this->_rules[$resource_id] = $rules;

		return $this;
	}

	public function get_rules($resource_id = NULL)
	{
		if (is_null($resource_id))
		{
			return $this->_rules;
		}
		else
		{
			if (!isset($this->_rules[$resource_id]))
			{
				return NULL;
			}
			else
			{
				return $this->_rules[$resource_id];
			}
		}
	}

	public function &get_rules_ptr($resource_id, $create_if_empty = FALSE)
	{
		if (!isset($this->_rules[$resource_id]))
		{
			if ($create_if_empty)
			{
				$this->set_rules($resource_id, array());
				$rules = &$this->_rules[$resource_id];
			}
			else
			{
				$rules = NULL;
			}
		}
		else
		{
			$rules = &$this->_rules[$resource_id];
		}

		return $rules;
	}


	public function add_rule($resource_id, $role_id, $permission, $type)
	{
		$rules = &$this->get_rules_ptr($resource_id, TRUE);
		$rules []= array($role_id, $permission, $type);

		return $this;
	}

	protected function _check_access($rules, $type, $role_id, $permission)
	{
		if (is_null($rules))
		{
			return FALSE;
		}
		else
		{
			foreach ($rules as $rule)
			{
				if ($rule[0] === $role_id && $rule[1] == $permission && $rule[2] == $type)
				{
					return TRUE;
				}
			}

			return FALSE;
		}
	}
}