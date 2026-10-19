<?php
/**
 * UserIdentity.php
 *
 * @author suxh
 */

namespace common\models;


use yii\web\IdentityInterface;

abstract class UserIdentity implements IdentityInterface
{
	/**
	 * @var integer 用户类型-系统用户
	 */
	const TYPE_SYSTEM = 1000;
	/**
	 * @var integer 用户类型-仓库用户
	 */
	const TYPE_STOREHOUSE = 2000;
	/**
	 * @var integer 用户类型-加盟商用户
	 */
	const TYPE_DISTRIBUTION = 3000;
	/**
	 * @var integer 用户类型-修理厂用户
	 */
	const TYPE_GARAGE = 4000;
	/**
	 * @var integer 用户类型-供应商用户
	 */
	const TYPE_SUPPLIER = 5000;

	/**
	 * @return integer 返回当前用户类型
	 */
	public abstract function getType();
	/**
	 * @return string 返回当前用户名称
	 */
	public abstract function getName();

	/**
	 * @return integer 返回当前系统用户ID，如果非系统用户，则统一返回100
	 */
	public function getSysUserId() {
		$type = $this->getType();
		return ($type == self::TYPE_SYSTEM)? $this->getId() : 100;
	}


	/**
	 * 返回格式化后的用户名称
	 * @param integer $type 用户类型
	 * @param integer $id 用户ID
	 * @param string $name 用户名称
	 */
	public static function getFullName($type, $id, $name) {
		$names = [
			self::TYPE_SYSTEM => '系统',
			self::TYPE_STOREHOUSE => '仓库',
			self::TYPE_DISTRIBUTION => '加盟商',
			self::TYPE_GARAGE => '修理厂',
		];

		return "[{$names[$type]}]$name";
	}

	public function setParam($name, $value) {
		\Yii::$app->getSession()->set($name, $value);
	}

	public function getParam($name) {
		return \Yii::$app->getSession()->get($name, null);
	}
}