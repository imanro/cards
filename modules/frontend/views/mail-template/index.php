<?php
namespace Frontend;

use Route;
use Kohana;
use HTML;
use Frontend\Module;
use DataGrid;
use Common\Model\Role;
use Common\Helper\Auth as AuthHelper;

/* @var $title string */
/* @var $model Common\Model\Mail\Template */
/* @var $data DatabaseResult */
/* @var $columns array */
/* @var $controller Frontend\Controller\MailTemplate */
?>

<h2><?= $title ?></h2>

<div class="control-row">
<?= HTML::link(Route::get('default')->uri(array('controller' => 'mail-template', 'action' => 'add')), Kohana::message('view', 'label.add', 'Add', Module::$name), array('class' => 'btn btn-primary'))?>
</div>

<?php $data_grid = DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				'name',
				array(
					'class' => 'DataGrid_Column_Action',
					'actions_map' => array(
						'update' => 'edit',
					),
					'content' => function($model, $key, $column){
						if($model->system == TRUE){
							$template_hold = $column->config('template');
							$column->config('template', '{update}');
							$retval = $column->data_content_default($model, $key);
							$column->config('template', $template_hold);
							return $retval;
						} else {
							return $column->data_content_default($model, $key);
						}
					}
				)
			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			)
		));

if(AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN)){
		$data_grid->add_column('code', 2);

	$data_grid->add_column(array(
		'attribute' => 'system',
		'content' => function($model, $attribute, $key) {
			return Kohana::message('enums/model/mail-template', 'system.' . $model->get($attribute), $model->get($attribute), Module::$name);
		},
	), 2);
}
?>

<?= $data_grid ?>