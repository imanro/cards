<?php

namespace Common\Model;

use Kohana;
use Common\Model\User;
use Model;
use ORM;
use Common\Model\User\Balance\Transaction;

class Affiliate extends Model {

	public static function affect_user_invitation(User $inviter, User $acceptor)
	{
		// trying to find same transaction
		$exists = ORM::factory('Common\Model\User\Balance\Transaction')->
		where('user_id', '=', $inviter->id)->
		where('type', '=', Transaction::TYPE_INCOME)->
		where('subject', '=', Transaction::SUBJECT_AFFILIATE_INVITE)->
		where('resource', '=', $acceptor->id)->count_all() > 0;

		if($exists) {
			throw new \Exception('User already received bonuce for this invitation');
		}

		// creating transaction
		$transaction = ORM::factory('Common\Model\User\Balance\Transaction');
		/* @var \Common\Model\User\Balance\Transaction  $transaction */

		$transaction->type = Transaction::TYPE_INCOME;
		$transaction->subject = Transaction::SUBJECT_AFFILIATE_INVITE;
		$value = Kohana::$config->load('settings')->get('bonuce_value_affiliate_invite');
		$transaction->value = $value;
		$transaction->resource = $acceptor->id;
		$transaction->user_id = $inviter->id;

		$transaction->apply();

		$transaction->save();
	}
}
