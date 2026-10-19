<?php

namespace app\models\system;

use common\models\Error;
use common\models\system\AllowLoginIpModel;

class AllowLoginIpModifyForm extends AllowLoginIpForm
{
	public $id;

	public function rules()
	{
		return array_merge($this->commonRules(), [[['id'], 'integer', 'min' => 1]]);
	}

	public function validateUniqueIp($attribute)
	{
		$result = AllowLoginIpModel::checkIp($this->$attribute, $this->id);
		if (Error::isError($result)) {
			$this->addError($attribute, $result->getError());
		}
	}
}
