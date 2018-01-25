<?php

namespace Frontend;

use Kohana;
use Common\Model\Role;
use Acl\Acl;
use Frontend\Controller\FrontendController;
use Acl\Rbac;
use HTML;

/**
 * @var $user \Common\Model\User
 * @var $controller \Common\Controller\FrontendController
 * @var $menu_navigation array
 */

/**
 * Menu Navigation
 */
if($user && $user->loaded()){
	$subject = $user;
} else {
	$role = new Role();
	$role->name = Role::NAME_GUEST;
	$subject = $role;
}

$timezone = date_default_timezone_get();

$acl = Acl::factory('Rbac');
/* @var $acl \Acl\Rbac */
$access_map_processed = array();
?>
<?php if(count($menu_navigation)):?>
<div class="col-sm-2 col-menu">

		<ul class="nav user-info">
    	<?php if(!is_null($user) && $user->loaded()): ?>
				<li class="user-balance">
					<div>
						<?=strtr(Kohana::message('view', 'message.your_balance', NULL, Module::$name), array('{value}' => HTML::tag('span', $user->get_balance()->value, array('class' => 'value'))));?>
						<div class="help"><?= strtr(Kohana::message('view', 'message.invite_users_to_increase_balance', NULL, Module::$name), array('{value}' => Kohana::$config->load('settings')->get('bonuce_value_affiliate_invite'))); ?></div>
					</div>

				</li>
			<?php endif; ?>
			<?php if(!is_null($user) && $user->loaded()): ?>
			<li class="user-timezone">
				<div>
			<?= strtr(Kohana::message('view', 'message.your_timezone', NULL, Module::$name), array('{timezone}' => $timezone)); ?>
			</div>
			</li>
			<?php endif; ?>
			</ul>


<ul class="nav menu-navigation">
<?php foreach($menu_navigation as $row): ?>
<?php
$resource = $row['resource'];
$class = $resource[0];
/* @var $class \Frontend\Controller\FrontendController */
$resource_id = FrontendController::format_resource_id($resource[0], $resource[1]);

if(!isset($access_map_processed[$class])) {
	$acl->setup_rules(FrontendController::prepare_access_map($class::access_map(), $class));
}
?>


<?php if($acl->has_access(Rbac::PERMISSION_EXEC, $subject, $resource_id)): ?>
<li><?= HTML::link($row['url'], $row['title'], isset($row['options'])? $row['options']: array()) ?></li>
<?php endif; ?>

<?php endforeach;?>
</ul>
</div>
<?php else: ?>
<?php endif; ?>
