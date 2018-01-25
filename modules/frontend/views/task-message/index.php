<?php

namespace Frontend\Controller;

use Kohana;
use DataGrid;
use Frontend\Module;
use HTML;
use Text;
use Widget_InPlace;
use Route;

/* @var $title string */
/* @var $add_form string */
/* @var $model Common\Model\Mail\Template */
/* @var $data DatabaseResult */
/* @var $columns array */
/* @var $controller FrontendController */
/* @var $callback_delivery callable */
/* @var $csv_import_form string */
/* @var $added_ids array */
?>


<?php
$rows_attributes = array();
if (is_array($added_ids))
{
	foreach ($added_ids as $key => $value)
	{
		$rows_attributes[$key] = array(
			'class' => 'imported'
		);
	}
}

?>
<div class="task-message-index-cont">
<h2><?= $title ?></h2>

<?= $add_form; ?>
<?= $csv_import_form; ?>

<?php $data_grid = DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				array(
					'attribute' => 'exec_date',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->label('exec_date'),
					'content' => function($model, $attribute, $key, $column) {
							return new Widget_InPlace(array(
								'content' => $model->get($attribute),
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit-ajax', 'id' => $model->get('id'))),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
								'js_callbacks' => array('start-edit' => 'var n = new Date();
									input.datetimepicker({
									useCurrent: false,
									locale: \'ru\',
									format: \'YYYY-MM-DD\',
									defaultDate: moment([n.getFullYear(), n.getMonth(), n.getDate(), 0, 0]).add(1, \'day\'),
									minDate: moment([n.getFullYear(), n.getMonth(), n.getDate(), 0, 0]).add(1, \'day\'),
									keyBinds: {enter: function(){input.trigger(inPlaceEvents.SAVE);}}});
								input.on(\'dp.hide\', function(e){
									input.trigger(inPlaceEvents.SAVE);
								});'),
							));
					}
				),
				array(
					'attribute' => 'recipient_name',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->label('recipient_name'),
					'content' => function($model, $attribute, $key, $column) {
						return new Widget_InPlace(array(
								'content' => $model->get($attribute),
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit-ajax', 'id' => $model->get('id'))),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
							));
					}
				),
				array(
					'attribute' => 'recipient_email',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->label('recipient_email'),
					'content' => function($model, $attribute, $key, $column) {
						return new Widget_InPlace(array(
								'content' => $model->get($attribute),
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit-ajax', 'id' => $model->get('id'))),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
							));
					}
				),
				array(
					'attribute' => 'second_recipient_name',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->label('second_recipient_name'),
					'content' => function($model, $attribute, $key, $column) {
						return new Widget_InPlace(array(
								'content' => $model->get($attribute)? $model->get($attribute) : '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
								'value' => $model->get($attribute)? $model->get($attribute) : '',
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit-ajax', 'id' => $model->get('id'))),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
							));
					}
				),

				array(
					'attribute' => 'mail_subject',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->label('mail_subject'),
					'content' => function($model, $attribute, $key, $column) {
							return new Widget_InPlace(array(
								'content' => $model->get($attribute),
								'tag' => 'span',
								'control' => ' <span class="glyphicon glyphicon-pencil in-place-control"></span>',
								'url' => Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit-ajax', 'id' => $model->get('id'))),
								'data' => array($attribute => '{value}'),
								'attributes' => array('class' => 'in-place'),
								'input_attributes' => array('class' => 'form-control'),
							));
					}
				),

				array(
					'attribute' => 'state',
					'label' => Kohana::message('view', 'label.last_delivery_state', NULL, Module::$name),
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
					$delivery = $model->delivery();
					if($delivery && $delivery->loaded()){
						$text = Kohana::message('enums/model/task-message-delivery', 'state.' . $delivery->get($attribute), $delivery->get($attribute), Module::$name);
						$html_class = $delivery->get($attribute);
					} else {
						$text = '[' . Kohana::message('view', 'message.not_defined', NULL, Module::$name) . ']';
						$html_class = '';
					}

					return HTML::tag('span', $text, array('class' => $html_class));
					}
				),
				array(
					'class' => 'DataGrid_Column_Action',
					'actions_map' => array(
						'update' => 'edit'
					)
				),
			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			),
			'after_get_data' => $callback_delivery,
			'rows_attributes' => $rows_attributes,
		));

		if(is_array($added_ids)){
			$data_grid->add_column(array(
						'attribute' => TRUE,
						'label' => ' ',
						'content' => function($model, $attribute, $key, $column) use ($added_ids)
						{
							if(isset($added_ids[$key])){
								return Kohana::message('view', 'message.imported_successfully', NULL, Module::$name);
							}
						}
					)
		);
		}
?>

<?= $data_grid; ?>

</div>
