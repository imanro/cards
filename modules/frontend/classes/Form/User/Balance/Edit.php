<?php
namespace Frontend\Form\User\Balance;

use Common\Model\User\Balance;
use Formo_ORM;
use Formo;


class Edit extends Balance {
	use Formo_ORM {
		get_form as get_form_default;
	}

	public function get_form(array $fields, Formo $form = NULL)
	{
		$this->_formo_alias = 'balance';
		return $this->get_form_default(array(
			'value'
		), $form);
	}
}
