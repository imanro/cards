<?php

namespace Frontend\Form\Task\Message;

use Kohana;
use Formo;
use Formo_Constructor;
use Frontend\Module;

class CsvImport implements Formo_Constructor {

	protected $_formo_alias = 'csv-import';

	public function get_form()
	{
		$form = Formo::form(['alias' => $this->_formo_alias]);

		$this->formo($form);
		return $form;
	}

	public function formo(Formo $form)
	{
		$form->set('config.module', Module::$name);
		$form->set('config.label_message_file', 'labels/form/task-message/csv-import');

		$form->add('csv_file', 'file');
		$form->csv_file->add_rules($this->_rules_csv_file()['csv_file']);
		$form->csv_file->set('error_messages', array('Upload::type' => Kohana::message('view', 'message.wrong_file_type', NULL, Module::$name)));

		return $form;
	}

	protected function _rules_csv_file()
	{
		return array(
			'csv_file' => array(
				array('not_empty'),
				array('Upload::type', array(':value', array('csv'))),
			));
	}

}