<?php
/* @var $page Pagination */

/* @var $total_pages int */
/* @var $previous_page int */
/* @var $current_page int */
/* @var $next_page int */

$page_range = 10;

$_is_period_rendered = FALSE;
$visible_range = ceil($page_range / 2);

$_range_offset_ceil = 0;
$_range_offset_floor = 0;
?>

<nav>
	<ul class="pagination">
	<?php if ($previous_page !== FALSE): ?>
		<li><a href="<?php echo HTML::chars($page->url($previous_page)) ?>" rel="prev">&lt;&lt;</a></li>
	<?php else: ?>
	<?php endif ?>

	<?php for ($i = 1; $i <= $total_pages; $i++): ?>

<?php $_range_offset_ceil = $current_page - $visible_range - 1;?>
<?php $_range_offset_floor = $total_pages - $current_page - $visible_range + 1;?>

		<?php if($i < ($current_page - $visible_range + ($_range_offset_floor < 0 ? $_range_offset_floor : 0))): ?>

		<?php if(!$_is_period_rendered): ?>
		<li><span>...</span></li>
		<?php $_is_period_rendered = true; ?>
		<?php endif; ?>
		<?php elseif($i > ($current_page + $visible_range - ($_range_offset_ceil < 0 ? $_range_offset_ceil : 0) - 1)): ?>
		<?php if(!$_is_period_rendered): ?>
		<li><span>...</span></li>
		<?php $_is_period_rendered = true; ?>
		<?php endif;?>
		<?php else: ?>
		<?php $_is_period_rendered = FALSE; ?>
		<?php if ($i == $current_page): ?>
			<li class="active"><span><?php echo $i ?></span></li>
		<?php else: ?>
			<li><a href="<?php echo HTML::chars($page->url($i)) ?>"><?php echo $i ?></a></li>
		<?php endif ?>
		<?php endif;?>

	<?php endfor; ?>

	<?php if ($next_page !== FALSE): ?>
		<li><a href="<?php echo HTML::chars($page->url($next_page)) ?>" rel="next">&gt;&gt;</a></li>
	<?php else: ?>
	<?php endif ?>
	</ul>
</nav>
