<?php
/* @var $menu_pages string */
/* @var $user \Common\Model\User */
namespace Frontend;

use Kohana;
use HTML;
use Route;
use URL;
use Frontend\Module;
use Common\Model\User\Balance;
?>

<?php

?>
<nav class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header col-sm-2">
       <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar-collapse" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?= URL::base(); ?>">
      	<?= $site_name ?>
      </a>
    </div>
    <div class="collapse navbar-collapse" id="main-navbar-collapse">
    	<?= $menu_pages; ?>

			<ul class="nav navbar-nav navbar-right navbar-login col-sm-4">
			<?php if(is_null($user) || !$user->loaded()): ?>
			<li>
				<div>
				<span class="user-info">
				<?= Kohana::message('view', 'message.not_logged_in', NULL, Module::$name); ?>
				</span>
				<span class="login-control"><?= HTML::link(Route::get('default')->uri(array(
				'action' => 'login',
				'controller' => 'auth')
				), Kohana::message('view', 'label.login', NULL, Module::$name)); ?>
				</span>
			</li>
			<?php else: ?>
			<li>
				<div>
				<span class="user-info"><?= strtr(Kohana::message('view', 'message.welcome', NULL, Module::$name), array('{username}' => HTML::link(Route::get('default')->uri(array(
				'action' => 'self-edit',
				'controller' => 'user')
				), $user->email . ' ' . HTML::tag('i', '', array('class' => 'glyphicon glyphicon-cog'))))); ?>
				</span>
				<span class="login-control">
				<?= HTML::link(Route::get('default')->uri(array(
				'action' => 'logout',
				'controller' => 'auth')
				), Kohana::message('view', 'label.logout', NULL, Module::$name)); ?>
				</span>
				</div>
			</li>
			<?php endif; ?>
			</ul>
			</div>
		</div>
 </nav>
<?php if(Kohana::$environment != Kohana::PRODUCTION): ?>
<div class="build-info">Build: <?= APP_BUILD; ?></div>
<?php endif; ?>