<?php

namespace Common\Model;

use ORM;
use Acl\Rbac\RoleInterface;

class Role extends ORM implements RoleInterface {

	const NAME_GUEST = 'guest';

	const NAME_LOGIN = 'login';

	const NAME_SUPERADMIN = 'superadmin';

	const ID_GUEST = 1;

	const ID_LOGIN = 2;

	const ID_SUPERADMIN = 3;

	protected $_table_name = 'role';

	protected $_table_columns = array(
		'id' => null,
		'name' => null,
		'description' => null,
	);

	// Relationships
	protected $_has_many = array(
		'users' => array('model' => 'User','through' => 'roles_users'),
	);

	/**
	 * @see RoleInterface
	 */
	public function get_role_id()
	{
		return $this->name;
	}

	public function rules()
	{
		return array(
			'name' => array(
				array('not_empty'),
				array('min_length', array(':value', 4)),
				array('max_length', array(':value', 32)),
			),
			'description' => array(
				array('max_length', array(':value', 255)),
			)
		);
	}

} // End Auth Role Model
