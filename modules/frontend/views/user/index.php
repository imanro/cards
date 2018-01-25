<?php

namespace Frontend\Controller;

use Frontend\Module;
use DataGrid;
use Kohana;
use Common\Model\User as UserModel;

/* @var $title string */
/* @var $controller Frontend\FrontendController */
/* @var $model Common\Model\Page */
/* @var $stat array */
?>

<h2><?= $title ?></h2>

<div class="stat-cont">
<dl class="stat col-sm-3">
<dt><?= Kohana::message('view', 'label.of_users', NULL, Module::$name);?>:</dt>
<dd><?= $stat['users']; ?></dd>
</dl>
<dl class="stat col-sm-3">
<dt><?= Kohana::message('view', 'label.of_deliveries', NULL, Module::$name);?>:</dt>
<dd><?= $stat['success_deliveries']; ?></dd>
</dl>
</div>

<?= DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				'id',
				'email',
				array(
					'attribute' => 'full_name',
					'class' => 'DataGrid_Column_Data',
					'label' => Kohana::message('view', 'label.full_name', NULL, Module::$name),
					'content' => function($model, $attribute, $key, $column) {
					/* @var $model \Common\Model\User */
						return $model->format_name();
					}
				),
				'create_time',
				array(
					'class' => 'DataGrid_Column_Data',
					'attribute' => 'value',
					'label' => $model->balance->label('value'),
					'content' => function($model, $attribute, $key, $column) {
						$value = $model->balance->get($attribute);
						if(is_null($value)){
							$value = Kohana::$config->load('settings')->get('default_balance');
						}
						return $value;
					}
				),

				array(
					'class' => 'DataGrid_Column_Data',
					'attribute' => 'delivery_count',
					'label' => Kohana::message('view', 'label.delivery_count', NULL, Module::$name),
					'content' => function($model, $attribute, $key, $column) {
						$value = $model->get($attribute);;
						if(is_null($value)){
							$value = 0;
						}
						return $value;
					}
				),

				array(
					'class' => 'DataGrid_Column_Action',
					'template' => '{delete} {update}',
					'actions_map' => array(
						'update' => 'edit'
					),
					'content' => function($model, $key, $column){
						if($model->id == UserModel::ID_ROOT){
							$template_hold = $column->config('template');
							$column->config('template', '{update}');
							$retval = $column->data_content_default($model, $key);
							$column->config('template', $template_hold);
							return $retval;
						} else {
							return $column->data_content_default($model, $key);
						}
					}
				),
			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			)
		)); ?>
