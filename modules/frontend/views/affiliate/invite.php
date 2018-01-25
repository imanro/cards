<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;

/* @var $form Formo */
/* @var $form_title string */
/* @var $model Frontend\Form\Affiliate\Invite */
/* @var $affiliate_link string */
?>

<h3><?= $form_title ?></h3>

<?= $form->attr(array('class' => 'form-horizontal mail-template'))->open(); ?>
<div class="col-lg-12 form-cont">

<div class="form-group <?= Form::class_toggle('has-error', $form->to->error()); ?>">
<?php /* To */ ?>
<?= Form::label_formo($form, 'to', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'to', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'to', array('class' => 'help-block')); ?>
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
<?= Form::input_formo($form, 'body', array('class' => 'form-control', 'required' => 'false')); ?>
<?= Form::error_formo($form, 'body', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group controls-row">
<div class="col-lg-offset-4 col-lg-8">
<?= Form::button('submit', Kohana::message('view', 'label.send', 'Send', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
</div>
</div>

<p class="help-block col-lg-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>
