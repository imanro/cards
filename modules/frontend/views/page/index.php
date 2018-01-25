<?php

namespace Frontend\Controller;

use Frontend\Module;
use DataGrid;
use Kohana;

/* @var $title string */
/* @var $controller Frontend\FrontendController */
/* @var $model Common\Model\Page */
/* @var $data DatabaseResult */
?>

<h2><?= $title ?></h2>

<?= DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				'title',
				'slug',
				array(
					'attribute' => 'is_hidden',
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
						return Kohana::message('enums/model/page', 'is_hidden.' . $model->get($attribute), $model->get($attribute), Module::$name);
					}
				),
				array(
					'class' => 'DataGrid_Column_Action',
					'template' => '{update}',
					'actions_map' => array(
						'update' => 'edit'
					)
				)
			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			)
		)); ?>
