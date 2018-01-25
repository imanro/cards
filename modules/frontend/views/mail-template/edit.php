<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;
use HTML;
use Route;

/* @var $form Formo */
/* @var $form_title string */
/* @var $model Frontend\Form\Mail\Template\Edit */
?>

<?= $form->attr(array('class' => 'form-horizontal mail-template'))->open(); ?>
<div class="col-lg-12 form-cont">
<h2><?= $form_title ?></h2>

<div class="form-group <?= Form::class_toggle('has-error', $form->name->error()); ?>">
<?php /* Name */ ?>
<?= Form::label_formo($form, 'name', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'name', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'name', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->subject->error()); ?>">
<?php /* Subject */ ?>
<?= Form::label_formo($form, 'subject', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'subject', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'subject', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->body->error()); ?>">
<?php /* Body */ ?>
<?= Form::label_formo($form, 'body', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">

<?= Form::input('switch-editor', 'simple', array('type' => 'radio', 'id' => 'switch-editor-simple', 'class' => 'switch-editor', 'checked' => 'checked', 'data-selector' => '#mail-template-edit-body')); ?>

<?= Form::label( 'switch-editor-simple', Kohana::message('view', 'label.simple_editor', NULL, Module::$name)) ?>

<?= Form::input('switch-editor', 'rich', array('type' => 'radio', 'id' => 'switch-editor-rich', 'class' => 'switch-editor', 'data-selector' => '#mail-template-edit-body')); ?>

<?= Form::label( 'switch-editor-rich', Kohana::message('view', 'label.rich_editor', NULL, Module::$name)) ?>

<?= Form::input_formo($form, 'body', array('class' => 'form-control', 'required' => 'false')); ?>

<p>
<?php foreach($model->get_variables_map(FALSE) as $row): ?>
<?php foreach($row as $name): ?>
<div class="template-variable-label"><?= $name; ?></div>
<?php endforeach;?>
<?php endforeach; ?>
</p>
<?= Form::error_formo($form, 'body', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group controls-row">
<div class="col-lg-offset-4 col-lg-8">
<?= Form::button('submit', Kohana::message('view', 'label.save', 'Save', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
<?= HTML::link(Route::get('default')->uri(array('controller' => 'mail-template', 'action' => 'index')), Kohana::message('view', 'label.cancel', 'Cancel', Module::$name), array('class' => 'control-link')) ?>
</div>
</div>

<p class="help-block col-lg-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>
