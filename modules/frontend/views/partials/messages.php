<?php
/* @var $messages array */
?>

<?php if(count($messages)): ?>
<div id="messages">

<div class="body">
<?php foreach($messages as $row ): ?>
<div class="alert alert-<?= $row[1]; ?> alert-dismissible fade in" role="alert"><?= $row[0]; ?></div>
<?php endforeach;?>
</div>
</div>
<?php endif;?>
