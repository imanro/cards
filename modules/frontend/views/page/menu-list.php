<?php
namespace Frontend\Controller;

/* @var $models \Common\Model\Page[] */
/* @var $active_slug string */

use HTML;
use Route;
?>
<ul class="nav navbar-nav col-sm-6">
<?php foreach($models as $model): ?>
<li class="<?= $model->slug == $active_slug? 'active': ''; ?>"><?= HTML::link(Route::get('page')->uri(array('slug' => $model->slug)), $model->title); ?></li>
<?php endforeach;?>
</ul>