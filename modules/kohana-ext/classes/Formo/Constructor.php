<?php

/**
 * Just Formo_ORM alike interface for building similar constructors for form without ORM base
 * @author manro
 */
interface Formo_Constructor {

	/**
	 * @return Formo
	 */
	public function get_form();

	/**
	 * @param Formo $form
	 */
	public function formo(Formo $form);
}