<?php
/**
 * FormModel.php
 *
 * @author suxh
 */

namespace common\models;

class FormModel extends \yii\base\Model
{
	public function loadPost($formName = null) {
		$data = \Yii::$app->request->post();

		if ($data) {
			// 去左右空格
			array_walk_recursive($data, function(&$value, $key) {
				$value = trim($value);
			});
		}

		return parent::load($data, $formName);
	}

	public function loadGet($formName = null) {
		$data = \Yii::$app->request->get();

		if ($data) {
			// 去左右空格
			array_walk_recursive($data, function(&$value, $key) {
				$value = trim($value);
			});
		}

		return parent::load($data, $formName);
	}

	/**
	 * 取得安全的属性信息，即当前场景，有相应验证规则的属性及其取值
	 * @return array 返回属性信息
	 */
	public function getSafeAttributes() {
		$attributes = $this->safeAttributes();
		return $this->getAttributes($attributes);
	}

	public function getError($attribute=null) {
		$errors = $this->getErrors();
		if ($attribute === null) {
			$errors = current($errors);
			return (is_array($errors))? $errors[0]:$errors;
		}
		else if (isset($errors[$attribute])) {
			$errors = $errors[$attribute];
			return (is_array($errors))? $errors[0]:$errors;
		}
		return null;
	}
}