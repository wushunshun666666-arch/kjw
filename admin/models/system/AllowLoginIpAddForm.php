<?php

namespace app\models\system;

use common\models\Error;
use common\models\system\AllowLoginIpModel;

class AllowLoginIpAddForm extends AllowLoginIpForm
{
	public function rules()
	{
		return $this->commonRules();
	}

	public function validateUniqueIp($attribute)
	{
		$result = AllowLoginIpModel::checkIp($this->$attribute);
		if (Error::isError($result)) {
			$this->addError($attribute, $result->getError());
		}
	}
}
