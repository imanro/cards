<?php

namespace Acl;

use Acl\Dac\SubjectInterface;
use Acl\Dac\ResourceInterface;

class Dac extends Acl {

	const PERMISSION_WRITE = 2;

	const PERMISSION_READ = 4;

	const PERMISSION_EXEC = 1;

	public function has_access($permission, $subject, $resource)
	{
		if(!$subject instanceof SubjectInterface) {
			throw new \Acl\Rbac\Exception(vsprintf('Subject must be an instance of %s', array(SubjectInterface::class)));
		}

		if(!$resource instanceof ResourceInterface){
			throw new \Acl\Rbac\Exception(vsprintf('Resource must be an instance of %s', array(ResourceInterface::class)));
		}

		if (is_null($resource->get_owner_id()) || $resource->get_owner_id() == $subject->get_id())
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}