<?php
/**
 * UserIdentity.php
 *
 * @author suxh
 */

namespace common\models\system;

class UserIdentity extends \common\models\UserIdentity
{
	public function __construct($user) {
		$this->user = $user;
	}

	public static function findIdentity($id) {
		$user = User::findById($id);
		if (!$user) {
			return null;
		}
		return new self($user);
	}

	public static function findIdentityByAccessToken($token, $type = null) {
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}

	public function getType() {
		return self::TYPE_SYSTEM;
	}

	public function getId() {
		return $this->user->id;
	}

	public function getName() {
		return $this->user->name;
	}

	public function getAuthKey() {

	}

	public function validateAuthKey($authKey) {

	}

	public function getUser() {
		return $this->user;
	}

	public function getUserModel() {
		return new UserModel($this->user);
	}

	/**
	 * @var User 用户对象
	 */
	private $user;
}