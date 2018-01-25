<?php
namespace Frontend;

use Kohana;
use DataGrid;
use Frontend\Module;
use Common\Model\User;

/**
 * @var $title string
 * @var $model Common\Model\User\Balance\Transaction
 * @var $callback_users callable
 */

?>
<h3><?= $title ?></h3>

<?= DataGrid::factory(array(
			'data_source' => $model,
			'columns' => array(
				'create_time',
				array(
					'label' => Kohana::message('view', 'label.invited_user', NULL, Module::$name),
					'attribute' => 'invited_user',
					'content' => function($model, $attribute, $key, $column) {
						if($model->$attribute instanceof User) {
							$user = $model->$attribute;
							/* @var $user Common\Model\User */
							return vsprintf('%s &lt;%s&gt;', array( $user->format_name(), $user->email ));
						}
					}
				),
				array(
					'label' => Kohana::message('view', 'label.bonuces', NULL, Module::$name),
					'attribute' => 'value',
					'content' => function($model, $attribute, $key, $column) {
						return vsprintf('+%s', array($model->$attribute));
					}
				),
			),
			'after_get_data' => $callback_users,
		)
	);
?>