<?php

use Frontend\Module;

/* @var $form Formo */
/* @var $form_title string */

?>


<?= $form->attr(array('class' => 'form-horizontal csv-import', 'enctype' => 'multipart/form-data'))->open(); ?>
<div class="col-lg-12 form-cont">
<fieldset>
<legend><?= $form_title; ?></legend>

<div class="form-group <?= Form::class_toggle('has-error', $form->csv_file->error()); ?>">
<?php /* Csv-file */ ?>
<?= Form::label_formo($form, 'csv_file', array('class' => 'control-label col-lg-4')); ?>
<div class="col-lg-8">
<?= Form::input_formo($form, 'csv_file', array('class' => 'form-control')); ?>
<?= Form::error_formo($form, 'csv_file', array('class' => 'help-block')); ?>
<p class="text-right"><?= HTML::link(Kohana::$config->load('common')->get('csv_import_example_url'), Kohana::message('view', 'label.download_csv_example', NULL, Module::$name)); ?></p>
</div>

</div>

<div class="form-group controls-row">
<div class="col-sm-offset-4 col-sm-8">
<?= Form::button('submit', Kohana::message('view', 'label.import', 'Import', Module::$name), array('class' => 'btn btn-primary', 'type' => 'submit')); ?>
</div>
</div>

</fieldset>
</div>
<?= $form->close(); ?>

