<?php
namespace Frontend;

use Kohana;

/* @var $title string */
/* @var $affiliate_link string */
/* @var $invite_form string */
/* @var $invite_bonus_history string */
?>
<h2><?= $title ?></h2>

<dl>
<dt><?= Kohana::message('view', 'title.affiliate_link', NULL, Module::$name); ?></dt>
<dd><a href="<?= $affiliate_link ?>"><?= $affiliate_link ?></a></dd>

<?= $invite_form; ?>
<?= $invite_bonus_history; ?>
</dl>
