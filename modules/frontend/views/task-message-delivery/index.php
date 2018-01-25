<?php

namespace Frontend\Controller;

use Kohana;
use Form;
use Common\Helper\Auth as AuthHelper;
use Frontend\Module;
use DataGrid;
use HTML;
use Text;
use Common\Model\Role;

/* @var $title string */
/* @var $controller Frontend\FrontendController */
/* @var $model Common\Model\Mail\Template */
/* @var $data DatabaseResult */
/* @var $filter_form Formo */
/* @var $stat array */
?>


<h2><?= $title ?></h2>

<?= $filter_form->attr(array('class' => 'form-horizontal col-sm-6', 'method' => 'get'))->open(); ?>
<?php /* Filters */ ?>

<?php /* User select (for superadmin only) */ ?>

<?php /* From/To */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $filter_form->from->error()); ?>">
<?= Form::label_formo($filter_form, 'from', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($filter_form, 'from', array('class' => 'form-control filter-date')); ?>
<?= Form::error_formo($filter_form, 'from', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $filter_form->to->error()); ?>">
<?= Form::label_formo($filter_form, 'to', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($filter_form, 'to', array('class' => 'form-control filter-date')); ?>
<?= Form::error_formo($filter_form, 'to', array('class' => 'help-block')); ?>
</div>
</div>

<?php if(AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN)): ?>
<div class="form-group <?= Form::class_toggle('has-error', $filter_form->user_name->error()); ?>">
<?= Form::label_formo($filter_form, 'user_name', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($filter_form, 'user_name', array('class' => 'form-control', 'autocomplete' => 'off')); ?>
<?= Form::input_formo($filter_form, 'user_id', array('class' => 'form-control')); ?>
<?= Form::error_formo($filter_form, 'user_name', array('class' => 'help-block')); ?>
</div>
</div>
<?php endif; ?>


<?php /* Input */ ?>
<div class="form-group controls-row">
<div class="col-sm-offset-4 col-sm-8">
<?= Form::button('submit', Kohana::message('view', 'label.show', 'Save', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
</div>
</div>
<?= $filter_form->close(); ?>

<div class="stat-cont">
<dl class="stat col-sm-3">
<dt><?= Kohana::message('view', 'label.of_deliveries', NULL, Module::$name);?>:</dt>
<dd><?= $stat['success_deliveries']; ?></dd>
</dl>
</div>


<?= DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				'create_time',
				array(
					'attribute' => 'recipient_name',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->task_message->label('recipient_name'),
					'content' => function($model, $attribute, $key, $column) {
						return strtr('{name} &lt;{email}&gt;', array(
							'{name}' => HTML::chars(Text::limit_chars($model->task_message->get($attribute), 100, '...')),
							'{email}' => HTML::chars(Text::limit_chars($model->task_message->get('recipient_email'), 100, '...'))));
					}
				),

				array(
					'attribute' => 'mail_subject',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->task_message->label('mail_subject'),
					'content' => function($model, $attribute, $key, $column) {
						return HTML::chars(Text::limit_chars($model->task_message->get($attribute), 100, '...'));
					}
				),

				array(
					'attribute' => 'mail_body',
					'class' => 'DataGrid_Column_Data',
					'label' => $model->task_message->label('mail_body'),
					'content' => function($model, $attribute, $key, $column) {
						return HTML::chars(Text::limit_chars($model->task_message->get($attribute), 100, '...'));
					}
				),
				array(
					'attribute' => 'state',
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
						$text = Kohana::message('enums/model/task-message-delivery', 'state.' . $model->get($attribute), $model->get($attribute), Module::$name);
						$html_class = $model->get($attribute);
						return HTML::tag('span', $text, array('class' => $html_class));
					}
				),
				array(
					'attribute' => 'error_text',
					'class' => 'DataGrid_Column_Data',
					'content' => function($model, $attribute, $key, $column) {
						return $model->get($attribute)? $model->get($attribute) : '[' . Kohana::message('view', 'message.not_defined', 'Not defined', Module::$name) . ']';
					}
				),

			),
			'route_params' => array(
				'controller' => strtolower($controller->request->controller_name()),  // use controller-name instead of controller anywhere, because controller() returns full class with namespace
				'action' => 'index'
			)
		));
?>