<?php

namespace Common;

use Kohana;
use Common\Module;
use ORM;
use HTML;
use Route;

/* @var \Common\Model\Task\Message[] $messages */

$default_message = ORM::factory('Common\Model\Task\Message');
/* @var \Common\Model\Task\Message[] $default_message */

$table_style = 'border: solid 1px #ccc; border-collapse: collapse; margin-top: 5px; margin-bottom: 5px;';
$th_style = $td_style = 'border: solid 1px #ccc; padding: 5px;';
?>
<table style="<?= $table_style; ?>">
<thead>
<th style="<?= $th_style; ?>">#</th>
<th style="<?= $th_style; ?>"><?= $default_message->label('exec_date'); ?></th>
<th style="<?= $th_style; ?>"><?= $default_message->label('recipient_name'); ?></th>
<th style="<?= $th_style; ?>"><?= $default_message->label('recipient_email'); ?></th>
<th style="<?= $th_style; ?>"><?= $default_message->label('mail_body'); ?></th>
<th style="<?= $th_style; ?>"></th>
</thead>
<tbody>
<?php
$counter = 1;
?>
<?php foreach($messages as $message): ?>
<td style="<?= $td_style; ?>"><?= $counter; ?>.</td>
<td style="<?= $td_style; ?>"><?= $message->exec_date ?></td>
<td style="<?= $td_style; ?>"><?= $message->recipient_name ?></td>
<td style="<?= $td_style; ?>"><?= $message->recipient_email ?></td>
<td style="<?= $td_style; ?>"><?= $message->mail_body ?></td>
<td style="<?= $td_style; ?>"><?=
HTML::link(Kohana::$config->load('settings')->get('site_url') . '/' . Route::get('default')->uri(array('controller' => 'task-message', 'action' => 'edit', 'id' => $message->id )), Kohana::message('view', 'label.edit', 'Edit', Module::$name));
?></td>
<?php endforeach; ?>
</tbody>
</table>
