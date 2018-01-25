<?php

class Auth_ORM extends Kohana_Auth_ORM {

	/**
	 * Gets the currently logged in user from the session (with auto_login check).
	 * Returns $default if no user is currently logged in.
	 *
	 * @param   mixed    $default to return in case user isn't logged in
	 * @return  mixed
	 */
	public function get_user($default = NULL)
	{
		// taking uid instead of full object to prevent of session_init -> deserialization -> reloading object ->
		// usage Auth -> session_init -> session_start() double error in some cases
		$uid = $this->_session->get($this->_config['session_key'], $default);

		if($uid >= 1){
			$user = ORM::factory('User', $uid);
		} else {
			$user = NULL;
		}

		if ($user === $default)
		{
			// check for "remembered" login
			if (($user = $this->auto_login()) === FALSE)
				return $default;
		}

		return $user;
	}

	protected function complete_login($user)
	{
		$user->complete_login();

		// Regenerate session_id
		$this->_session->regenerate();

		// Store uid in session
		$this->_session->set($this->_config['session_key'], $user->id);

		return TRUE;
	}


	/**
	 * Checks if a session is active. Checking for Model_User removed
	 *
	 * @param   mixed    $role Role name string, role ORM object, or array with role names
	 * @return  boolean
	 */
	public function logged_in($role = NULL)
	{
		// Get the user from the session
		$user = $this->get_user();

		if ( ! $user){
			return FALSE;
		}
		if ($user instanceof Auth_ORM_UserInterface AND $user->loaded())
		{
			// If we don't have a roll no further checking is needed
			if ( ! $role)
				return TRUE;

			if (is_array($role))
			{
				// Get all the roles
				$roles = ORM::factory('Role')
							->where('name', 'IN', $role)
							->find_all()
							->as_array(NULL, 'id');

				// Make sure all the roles are valid ones
				if (count($roles) !== count($role))
					return FALSE;
			}
			else
			{
				if ( ! is_object($role))
				{
					// Load the role
					$roles = ORM::factory('Role', array('name' => $role));

					if ( ! $roles->loaded())
						return FALSE;
				}
				else
				{
					$roles = $role;
				}
			}
			return $user->has('roles', $roles);
		} else {
			throw new Kohana_Exception(strtr('Session user is not of a :class', array(':class' => Auth_ORM_UserInterface::class)));
		}
	}

}