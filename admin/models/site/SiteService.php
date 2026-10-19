<?php
/**
 * SiteService.php
 *
 * @author suxh
 */

namespace app\models\site;

use Yii;
use app\models\Service;
use common\models\system\User;
use common\models\system\UserIdentity;
use common\caching\LoginCache;
use common\realtime\NoticeTokenAdapter;
use mylibs\api\cms\FeedbackClient;
use mylibs\yii\config\Configure;

class SiteService extends Service
{
	public function login(&$outOfLimit, $username, $password) {
		$cache = new LoginCache();
		$outOfLimit = false;
		$user = User::findByUsername($username);
		if (!$user || !$user->validatePassword($password)) {
			$outOfLimit = ($cache->count() >= 3);
			return $this->setError('用户名或密码错误。');
		}
		if ($user->isFreeze()) {
			return $this->setError('帐号已冻结。');
		}
		if ($user->isNoLogin()) {
			return $this->setError('不可登录帐号。');
		}

		$cache->reset();
		$identity = new UserIdentity($user);
		$identity->setParam('name', $user->name);
		return Yii::$app->user->login($identity);
	}

	public function getProfile() {
		$identity = \Yii::$app->user->getIdentity();
		$user = $identity->getUserModel();

		return $user->getData();
	}

	public function modifyProfile(ProfileForm $form) {
		$identity = \Yii::$app->user->getIdentity();
		$user = $identity->getUserModel();

		$attributes = [
			'tel' => $form->tel,
			'mobi' => $form->mobi,
			'email' => $form->email,
			'note' => $form->note,
		];

		if (!$user->modify($attributes)) {
			return $this->addErrors($user->getErrors());
		}

		return true;
	}

	public function modifyPassword(ModifyPasswordForm $form) {
		$oldPassword = $form->oldPassword;
		$newPassword = $form->newPassword;

		$identity = \Yii::$app->user->getIdentity();
		$user = $identity->getUserModel();
		if (!$user->validatePassword($oldPassword)) {
			return $this->setError('旧密码错误');
		}
		if (!$user->modifyPassword($newPassword)) {
			return $this->addErrors($user->getErrors());
		}

		// 退出系统重新登录
		\Yii::$app->user->logout();

		return true;
	}

	public function feedback($model) {
		$title     = $model->title;
		$content   = $model->content;
		$userId    = $this->getUserId();
		$userName  = $this->getUserName();
		$platform  = FeedbackClient::PLATFORM_ADMIN_PC;

		$client = new FeedbackClient();
		$result = $client->add($title,$content,$userId,$userName,$platform);

		if (!$result) {
			return $this->setError($client->getError());
		}

		return true;
	}

	public function getMessageCenter() {
	    $config = new Configure();
	    $config = $config->loadCommonConfig('message-center');

	    return [
	        'server' => $config['server'],
	        'token' => NoticeTokenAdapter::issue($this->getUserId(), $this->getUserName()),
	    ];
	}
}
