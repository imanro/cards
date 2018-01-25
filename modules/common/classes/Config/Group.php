<?php
// The only place without of namespaces in Common\ namespace because It depends of kohana_config, and it not namespaced too

class Config_Group  extends Kohana_Config_Group {
	public function get($key, $default = NULL)
	{
		if ($this->_group_name == 'settings')
		{
			$model = Model::factory('Common\Model\Settings');
			/* @var $model \Common\Model\Settings */
			$model->load();
			return $model->config($key);
		}
		else
		{
			return $this->offsetExists($key) ? $this->offsetGet($key) : $default;
		}
	}
}