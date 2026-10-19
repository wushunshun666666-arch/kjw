<?php
/**
 * LogicModel.php
 *
 * @author suxh
 */

namespace common\models;

class LogicModel extends \yii\base\Component
{
	/**
	 * 设置逻辑错误
	 * @param string $error 错误信息
	 * @return boolean 固定返回false
	 */
	public function setError($error) {
		$this->errors['_logic'][] = $error;

		return false;
	}

	public function getError($attribute=null) {
		if ($attribute === null) {
			$attribute = '_logic';
			$errors = isset($this->errors[$attribute])? $this->errors[$attribute] : current($this->errors);
			return (is_array($errors))? $errors[0]:$errors;
		}
		else if (isset($this->errors[$attribute])) {
			$errors = $this->errors[$attribute];
			return (is_array($errors))? $errors[0]:$errors;
		}
		return null;
	}

	public function addError($attribute, $error = '') {
		$this->errors[$attribute][] = $error;
		return false;
	}

	public function addErrors(array $items) {
		foreach ($items as $attribute => $errors) {
			if (is_array($errors)) {
				foreach($errors as $error) {
					$this->addError($attribute, $error);
				}
			} else {
				$this->addError($attribute, $errors);
			}
		}
		return false;
	}

	public function getErrors($attribute=null) {
		if ($attribute === null) {
			return $this->errors;
		}
		else {
			return isset($this->errors[$attribute])? $this->errors[$attribute]:[];
		}
	}

	public function hasErrors($attribute = null) {
		if ($attribute === null) {
			return !empty($this->errors);
		}
		else {
			return isset($this->errors[$attribute]);
		}
	}

	public function clearErrors($attribute = null) {
		if ($attribute === null) {
			$this->errors = [];
		} else {
			unset($this->errors[$attribute]);
		}
	}

	/**
	 * @return integer 返回当前用户对应的系统用户ID
	 */
	public static function getSysUserId() {
		$identity = self::getIdentity();
		return $identity->getSysUserId();
	}

	/**
	 * @return integer 返回当前用户的用户类型
	 */
	public static function getUserType() {
		$identity = self::getIdentity();
		return $identity->getType();
	}

	/**
	 * @return integer 返回当前用户的ID
	 */
	public static function getUserId() {
		$identity = self::getIdentity();
		return $identity->getId();
	}

	/**
	 * @return integer 返回当前用户的名称
	 */
	public static function getUserName() {
		$identity = self::getIdentity();
		return $identity->getName();
	}

	public static function setIdentity($identity) {
		self::$identity = $identity;
	}

	public static function getIdentity() {
		if (!self::$identity) {
			self::$identity = \Yii::$app->user->getIdentity();
		}

		return self::$identity;
	}

	private static $identity = null;

	private $errors = [];
}