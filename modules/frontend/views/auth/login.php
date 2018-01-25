<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;
use HTML;
use Route;


/* @var $form Formo */
/* @var $form_title string */
/* @var $is_layout bool */

$cont_class = $is_layout? 'col-xs-offset-2 col-xs-8 col-lg-offset-3 col-lg-6' : 'col-xs-10 col-xs-offset-1 col-sm-12 col-sm-offset-0';
?>

<?= $form->attr(array('class' => 'form-horizontal'))->open(); ?>
<div class="<?= $cont_class ?> login-cont center-form-cont">
<h2><?= $form_title ?></h2>

<div class="form-group <?= Form::class_toggle('has-error', $form->email->error()); ?>">
<?php /* Email */ ?>
<?= Form::label_formo($form, 'email', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'email', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'email', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->password->error()); ?>">
<?php /* Password */ ?>
<?= Form::label_formo($form, 'password', array('class' => 'control-label col-sm-4')); ?>
<div class="col-sm-8">
<?= Form::input_formo($form, 'password', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'password', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->remember_me->error()); ?>">
<div class="col-sm-8 col-sm-offset-4 checkbox-single">
<?= Form::input_formo($form, 'remember_me', array()); ?>
<?= Form::label_formo($form, 'remember_me', array()); ?>
<?= Form::error_formo($form, 'remember_me', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group buttons">
<div class="col-sm-offset-4 col-sm-8">
<?= Form::button('submit',
	Kohana::message('view', 'label.login', 'Login', Module::$name),
	array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
<?php if($is_layout): ?>

<?= HTML::link(Route::get('default')->uri(array('controller' => 'index', 'action' => 'index')),
	Kohana::message('view', 'label.cancel', 'Cancel', Module::$name),
	array('class' => 'control-link')) ?>
<?php endif; ?>

</div>
</div>

<p class="help-block col-sm-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

<div class="form-group links">
<div class="col-lg-12">

<?php if($is_layout): ?>
<?= HTML::link(Route::get('default')->uri(array('controller' => 'auth', 'action' => 'signup')),
		Kohana::message('view', 'label.signup', NULL, Module::$name),
	array('class' => 'control-link')); ?> |
<?php endif; ?>

<?= HTML::link(Route::get('default')->uri(array('controller' => 'auth', 'action' => 'password-reset-ask')),
	Kohana::message('view', 'label.forget_password', NULL, Module::$name),
	array('class' => 'control-link')); ?>

</div>
</div>


</div>
<?= $form->close(); ?>
