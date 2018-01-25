<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;
use HTML;
use Route;
use ORM;

/* @var $form Formo */
/* @var $form_title string */
/* @var $mail_template_model Common\Model\Mail\Template */
/* @var $model Frontend\Form\Mail\Template\Edit */
/* @var $user Common\Model\User */
/* @var $layout boolean */
?>

<?= $form->attr(array('class' => 'form-horizontal task-message'))->open(); ?>
<div class="col-lg-12 form-cont">

<?php if($form_title): ?>
<h2><?= $form_title ?></h2>
<?php endif; ?>

<fieldset>
<?php /* Recipient Name */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->recipient_name->error()); ?>">
<?= Form::label_formo($form, 'recipient_name', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'recipient_name', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'recipient_name', array('class' => 'help-block')); ?>
</div>
</div>

<?php /* Recipient Email */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->recipient_email->error()); ?>">
<?= Form::label_formo($form, 'recipient_email', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'recipient_email', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'recipient_email', array('class' => 'help-block')); ?>
</div>
</div>

<?php /* Second_recipient Name */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->second_recipient_name->error()); ?>">
<?= Form::label_formo($form, 'second_recipient_name', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'second_recipient_name', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'second_recipient_name', array('class' => 'help-block')); ?>
</div>
</div>
</fieldset>

<fieldset>
<?php /* Templates list */ ?>
<?php $templates_options = ORM::factory('Common\Model\Mail\Template')->where_current_user()->find_all()->as_array('id', 'name'); ?>
<div class="form-group">
<?= Form::label('mail_template_id', Kohana::message('view', 'label.mail_template'), array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::select('', $templates_options, NULL, array('class' => 'form-control', 'id' => 'mail_template_id')) ?>
</div>
</div>

<?php /* Mail Subject */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->mail_subject->error()); ?>">
<?= Form::label_formo($form, 'mail_subject', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'mail_subject', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'mail_subject', array('class' => 'help-block')); ?>
</div>
</div>

<?php /* Mail Body */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->mail_body->error()); ?>">
<?= Form::label_formo($form, 'mail_body', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">

<?= Form::input('switch-editor', 'simple', array('type' => 'radio', 'id' => 'switch-editor-simple', 'class' => 'switch-editor', 'checked' => 'checked', 'data-selector' => '#task-message-edit-mail_body')); ?>

<?= Form::label( 'switch-editor-simple', Kohana::message('view', 'label.simple_editor', NULL, Module::$name)) ?>

<?= Form::input('switch-editor', 'rich', array('type' => 'radio', 'id' => 'switch-editor-rich', 'class' => 'switch-editor', 'data-selector' => '#task-message-edit-mail_body')); ?>

<?= Form::label( 'switch-editor-rich', Kohana::message('view', 'label.rich_editor', NULL, Module::$name)) ?>

<?= Form::input_formo($form, 'mail_body', array('class' => 'form-control', 'required' => 'false')); ?>

<p>

<?php foreach($mail_template_model->get_variables_map(FALSE) as $row): ?>
<?php foreach($row as $name): ?>
<div class="template-variable-label"><?= $name; ?></div>
<?php endforeach;?>
<?php endforeach; ?>
</p>
<?= Form::error_formo($form, 'mail_body', array('class' => 'help-block')); ?>
</div>
</div>

<script>
template_variables_data=<?= json_encode(array('my_first_name' => $user->first_name, 'my_last_name' => $user->last_name)); ?>;
template_variables_map=<?= json_encode($mail_template_model->get_variables_map(FALSE)); ?>;
</script>

<div id="task-message-preview" class="col-sm-offset-4 col-sm-8">
<h4><?= Kohana::message('view', 'title.preview', NULL, Module::$name);  ?></h4>

<dl class="subject"><dt><?= Kohana::message('view', 'title.mail_subject', NULL, Module::$name); ?>:</dt><dd class="content"></dd></dl>
<dl class="body"><dt><?= Kohana::message('view', 'title.mail_body', NULL, Module::$name); ?>:</dt><dd class="content"></dd></dl>
</div>

</fieldset>

<?php /* Exec Time */ ?>
<div class="form-group <?= Form::class_toggle('has-error', $form->exec_date->error()); ?>">
<?= Form::label_formo($form, 'exec_date', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'exec_date', array('class' => 'form-control exec-date')); ?>
<?= Form::error_formo($form, 'exec_date', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group controls-row">
<div class="col-sm-offset-4 col-sm-8">
<?= Form::button('submit', Kohana::message('view', 'label.save', 'Save', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
<?php if($layout): ?>
<?= HTML::link(Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'index')), Kohana::message('view', 'label.cancel', 'Cancel', Module::$name), array('class' => 'control-link')) ?>
<?php endif; ?>
</div>
</div>

<p class="help-block col-sm-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>