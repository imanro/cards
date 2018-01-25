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

<?= $form->attr(array('class' => 'form-horizontal page'))->open(); ?>
<div class="col-lg-12 form-cont">
<h2><?= $form_title ?></h2>

<div class="form-group <?= Form::class_toggle('has-error', $form->title->error()); ?>">
<?php /* Title */ ?>
<?= Form::label_formo($form, 'title', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'title', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'title', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->slug->error()); ?>">
<?php /* Slug */ ?>
<?= Form::label_formo($form, 'slug', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'slug', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'slug', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->text->error()); ?>">
<?php /* Text */ ?>
<?= Form::label_formo($form, 'text', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">

<?= Form::input('switch-editor', 'simple', array('type' => 'radio', 'id' => 'switch-editor-simple', 'class' => 'switch-editor', 'checked' => 'checked', 'data-selector' => '#page-edit-text')); ?>

<?= Form::label( 'switch-editor-simple', Kohana::message('view', 'label.simple_editor', NULL, Module::$name)) ?>

<?= Form::input('switch-editor', 'rich', array('type' => 'radio', 'id' => 'switch-editor-rich', 'class' => 'switch-editor', 'data-selector' => '#page-edit-text')); ?>

<?= Form::label( 'switch-editor-rich', Kohana::message('view', 'label.rich_editor', NULL, Module::$name)) ?>

<?= Form::input_formo($form, 'text', array('class' => 'form-control', 'required' => 'false')); ?>
</div>
</div>

<div class="form-group controls-row">
<div class="col-lg-offset-4 col-lg-8">
<?= Form::button('submit', Kohana::message('view', 'label.save', 'Save', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
<?= HTML::link(Route::get('default')->uri(array('controller' => 'page', 'action' => 'index')), Kohana::message('view', 'label.cancel', 'Cancel', Module::$name), array('class' => 'control-link')) ?>
</div>
</div>

<p class="help-block col-lg-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>
