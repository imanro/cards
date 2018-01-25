<?php

namespace Frontend;

use Kohana;
use Frontend\Module;
use DataGrid;
use Widget_InPlace;
use Route;

/* @var $title string */
/* @var $model \Common\Model\Settings */
/* @var $controller Frontend\Controller\Settings */
?>

<h2><?= $title ?></h2>

<?= DataGrid::factory(array(
			'data_source' => $model->get_prepared_data(),
			'columns' => array(
				array(
					'attribute' => 'key',
					'label' => Kohana::message('labels/model/settings', 'key', NULL, Module::$name),
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
					return Kohana::message('settings', $model[$attribute], $model[$attribute], Module::$name);
					}
				),
				array(
					'attribute' => 'value',
					'label' => Kohana::message('labels/model/settings', 'value', NULL, Module::$name),
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
					return new Widget_InPlace(array(
								'content' => $model[$attribute],
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'settings', 'action' => 'edit-ajax', 'id' => $model['key'])),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
							));
					}
				),
			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			)
		));
?>
