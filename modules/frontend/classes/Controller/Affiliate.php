<?php

namespace Frontend\Controller;

use Frontend\Module;
use Kohana;
use Form_FormAbstract;
use Route;
use Request;
use View;
use URL;
use Common\Model\Role;
use Acl\Rbac;
use Common\Helper\Auth as AuthHelper;
use Common\Model\Mail\Template;
use Flasher;
use Common\Messenger;
use HTTP_Request;
use ORM;
use Common\Model\User\Balance\Transaction;
use DataGrid;

class Affiliate extends FrontendController {

	public static function access_map()
	{
		return array(
			array(
				Rbac::MAP_VAR_RESOURCES => 'index',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'invite',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
			array(
				Rbac::MAP_VAR_RESOURCES => 'invite_bonus_history',
				Rbac::MAP_VAR_ROLES => array(Role::NAME_LOGIN)
			),
		);
	}

	public function action_index()
	{
		$title = Kohana::message('view', 'title.affiliation_program', NULL, Module::$name);
		$this->action_invite();

		$user = AuthHelper::get_current_user();

		$user->get_balance()->spend();

		$affiliate_link = URL::site(Route::get('affiliate_signup')->uri(array('code' => $user->affiliate_code )), 'http');

		$invite_form = Request::factory(Route::get('default')->uri(array(
				'controller' => 'affiliate',
				'action' => 'invite',
				'layout' => 0
			)))->execute();

		$invite_bonus_history = Request::factory(Route::get('default')->uri(array(
				'controller' => 'affiliate',
				'action' => 'invite_bonus_history',
				'layout' => 0
			)))->execute();

		self::$template->title = $title;

		self::$template->content = View::factory('affiliate/index', array(
			'invite_form' => $invite_form,
			'invite_bonus_history' => $invite_bonus_history,
			'title' => $title,
			'controller' => $this,
			'affiliate_link' => $affiliate_link,
		), Module::$name);
	}

	public function action_invite()
	{
		$title =  Kohana::message('view', 'title.invite_friend', NULL, Module::$name);

		$user = AuthHelper::get_current_user();
		$affiliate_link = URL::site(Route::get('affiliate_signup')->uri(array('code' => $user->affiliate_code )), 'http');

		$invite_mail_template = Template::create_template_affiliate_invite();
		/* @var \Common\Model\Mail\Template $invite_mail_template */
		$invite_mail_template->variables_data(array('link' => $affiliate_link, 'my_first_name' => $user->first_name, 'my_email' => $user->email));
		$invite_mail_template->process();

		$form_model = Form_FormAbstract::factory('Frontend\Form\Affiliate\Invite');
		$form = $form_model->get_form();

		$form->subject->val($invite_mail_template->subject);
		$form->body->val($invite_mail_template->body);

		if ($this->request->method() == HTTP_Request::POST)
		{
			if ($form->load($this->request->post())->validate())
			{
				$result = false;
				try
				{
					if (Messenger::send_user_message($user, $form->to->val(), $form->subject->val(), $form->body->val()))
					{
						$result = true;
						$this->_add_flash_message('message.invitation_sent_successfully', Flasher::MESSAGE_SUCCESS);
					}
					else
					{
						$this->_add_flash_message('message.mail_sending_failed', Flasher::MESSAGE_ERROR);
					}
				}
				catch (\Exception $e)
				{
					\HTTP_Exception::factory(500, Kohana::message('view', 'message.mail_sending_failed', NULL, Module::$name), NULL, $e);
				}

				if ($result)
				{
					$this->redirect(Route::get('default')->uri(array(
						'controller' => 'affiliate',
						'action' => 'index'
					)));
					// redirect
				}

			}
			else
			{
				$this->_add_flash_message('message.fix_form_errors', Flasher::MESSAGE_WARNING);
			}
		}

		self::$template->content = View::factory('affiliate/invite', array(
			'form' => $form,
			'form_title' => $title,
			'controller' => $this,
		), Module::$name);
	}

	public function action_invite_bonus_history()
	{
		$title =  Kohana::message('view', 'title.invite_bonus_history', NULL, Module::$name);

		$user = $this->_get_current_user();

		$callback_users = function(DataGrid $datagrid) {
			$result = $datagrid->data();
			$model_user = ORM::factory('Common\Model\User');
			$ids = $model_user->extract_column_values($result, 'resource');

			if(count($ids)) {
				$users = $model_user->where('id', 'IN', $ids)->find_all();
				$model_user->pull_into_transactions($users, $datagrid->data());
			}
		};

		$model = ORM::factory('Common\Model\User\Balance\Transaction');
		/* @var \Common\Model\User\Balance\Transaction $model */
		$model->where('user_id', '=', $user->id)->
		//join_affiliate_invite_users()->
		where('type', '=', Transaction::TYPE_INCOME)->
		where('subject', '=', Transaction::SUBJECT_AFFILIATE_INVITE)->
		order_by('create_time', 'DESC');

		self::$template->content = View::factory('affiliate/invite_bonus_history', array(
			'title' => $title,
			'model' => $model,
			'controller' => $this,
			'callback_users' => $callback_users,
		), Module::$name);
	}
}