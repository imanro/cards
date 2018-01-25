<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;
use HTML;
use Route;

/* @var $form Formo */
/* @var $form_title string */
?>

<?= $form->attr(array('class' => 'form-horizontal'))->open(); ?>
<div class="center-form-cont col-xs-offset-2 col-xs-8 col-lg-offset-3 col-lg-6">
<h2><?= $form_title ?></h2>
<p class="form-description"><?= Kohana::message('view', 'message.password_reset_ask_description', NULL, Module::$name) ?></p>
<div class="form-group <?= Form::class_toggle('has-error', $form->email->error()); ?>">
<?php /* Email */ ?>
<?= Form::label_formo($form, 'email', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'email', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'email', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group">
<div class="col-sm-offset-4 col-lg-8">
<?= Form::button('submit', Kohana::message('view', 'label.continue', NULL, Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>

<?= HTML::link(Route::get('default')->uri(array('controller' => 'index', 'action' => 'index')), Kohana::message('view', 'label.cancel', 'Cancel', Module::$name), array('class' => 'control-link')) ?>
</div>
</div>

<p class="help-block col-lg-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>
