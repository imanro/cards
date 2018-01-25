<?php
namespace Frontend;

use Frontend\Module;
use Form;
use Kohana;
use HTML;
use Route;
use Common\Model\Mail\Settings\Template;
use Common\Helper\Auth as AuthHelper;
use Common\Model\Role;

/* @var $form Formo */
/* @var $form_title string */
/* @var $is_layout bool */
/* @var $controller \Frontend\Controller\User */

?>

<?= $form->attr(array('class' => 'form-horizontal', 'autocomplete' => 'off'))->open(); ?>
<div class="col-lg-12 user-edit-cont form-cont">
<h2><?= $form_title ?></h2>

<div class="form-group <?= Form::class_toggle('has-error', $form->email->error()); ?>">
<?php /* Email */ ?>
<?= Form::label_formo($form, 'email', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'email', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'email', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->password->error()); ?>">
<?php /* Password */ ?>
<?= Form::label_formo($form, 'password', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'password', array('class' => 'form-control', 'autocomplete' => 'off')); ?>
<?= Form::error_formo($form, 'password', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->password_confirm->error()); ?>">
<?php /* Password Confirm */ ?>
<?= Form::label_formo($form, 'password_confirm', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'password_confirm', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'password_confirm', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->first_name->error()); ?>">
<?php /* First_name */ ?>
<?= Form::label_formo($form, 'first_name', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'first_name', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'first_name', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->last_name->error()); ?>">
<?php /* Last_name */ ?>
<?= Form::label_formo($form, 'last_name', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'last_name', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'last_name', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->timezone_id->error()); ?>">
<?php /* Timezone_id */ ?>
<?= Form::label_formo($form, 'timezone_id', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'timezone_id', array('class' => 'form-control')); ?>
<span class="help-block"><?= Kohana::message('view', 'label.timezone_id_edit_help', NULL, Module::$name) ?></span>
<?= Form::error_formo($form, 'timezone_id', array('class' => 'help-block')); ?>
</div>
</div>

<fieldset>
<legend><?= Kohana::message('view', 'title.smtp_credentials', NULL, Module::$name) ?></legend>

<?php /* Mail settings templates */ ?>
<?php $templates_options = array_merge(array('' => ''), Template::factory()->get_form_options_template()); ?>
<div class="form-group">
<?= Form::label('mail_settings_template', Kohana::message('view', 'label.mail_settings_template'), array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::select('', $templates_options, NULL, array('class' => 'form-control', 'id' => 'mail_settings_template')) ?>
</div>
</div>

<hr/>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_host->error()); ?>">
<?php /* Mail_smtp_host */ ?>
<?= Form::label_formo($form, 'mail_smtp_host', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'mail_smtp_host', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'mail_smtp_host', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_port->error()); ?>">
<?php /* Mail Smtp Port */ ?>
<?= Form::label_formo($form, 'mail_smtp_port', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'mail_smtp_port', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'mail_smtp_port', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_start_tls->error()); ?>">
<?php /* Mail Smtp Start_tls */ ?>
<?= Form::label_formo($form, 'mail_smtp_start_tls', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8 checkbox-single">
<?= Form::input_formo($form, 'mail_smtp_start_tls', array()); ?>
<?= Form::error_formo($form, 'mail_smtp_start_tls', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_tls_ssl->error()); ?>">
<?php /* Mail Smtp Tls_ssl */ ?>
<?= Form::label_formo($form, 'mail_smtp_tls_ssl', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8 checkbox-single">
<?= Form::input_formo($form, 'mail_smtp_tls_ssl', array()); ?>
<?= Form::error_formo($form, 'mail_smtp_tls_ssl', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_user->error()); ?>">
<?php /* Mail_smtp_user */ ?>
<?= Form::label_formo($form, 'mail_smtp_user', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'mail_smtp_user', array('class' => 'form-control')); ?>
<span class="help-block"><?= Kohana::message('view', 'label.mail_smtp_user_help', NULL, Module::$name) ?></span>
<?= Form::error_formo($form, 'mail_smtp_user', array('class' => 'help-block')); ?>
</div>
</div>

<div class="form-group <?= Form::class_toggle('has-error', $form->mail_smtp_password->error()); ?>">
<?php /* Mail Smtp Password */ ?>
<?= Form::label_formo($form, 'mail_smtp_password', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'mail_smtp_password', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'mail_smtp_password', array('class' => 'help-block')); ?>
</div>
</div>
</fieldset>

<?php if(AuthHelper::get_current_user()->has_role(Role::ID_SUPERADMIN)): ?>
<fieldset>
<legend><?= Kohana::message('view', 'title.balance', NULL, Module::$name) ?></legend>
<div class="form-group <?= Form::class_toggle('has-error', $form->balance->value->error()); ?>">
<?php /* balance */ ?>
<?= Form::label_formo($form->balance, 'value', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form->balance, 'value', array('class' => 'form-control')); ?>
<?= Form::error_formo($form->balance, 'value', array('class' => 'help-block')); ?>
</div>
</div>
</fieldset>
<?php endif; ?>

<div class="form-group">
<div class="col-lg-offset-4 col-lg-8">
<?= Form::button('submit',
	Kohana::message('view', 'label.save', 'Save', Module::$name),
	array('class' => 'btn btn-primary', 'type' => 'submit')); ?>

<?php
$cancel_uri = $controller->request->action() == 'self-edit'?
	Route::get('default')->uri(array('controller' => 'index', 'action' => 'index')) :
	Route::get('default')->uri(array('controller' => 'user', 'action' => 'index'));
?>

<?= HTML::link($cancel_uri,
	Kohana::message('view', 'label.cancel', 'Cancel', Module::$name),
	array('class' => 'control-link')) ?>

</div>
</div>

<p class="help-block col-lg-offset-4 required"><?= Kohana::message('view', 'message.marked_fields_are_required', NULL, Module::$name); ?></p>

</div>
<?= $form->close(); ?>
