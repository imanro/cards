<?php

namespace Acl\Rbac;

interface SubjectInterface {
	public function get_roles();

	public function has_role($id);
}