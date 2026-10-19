<?php

namespace app\models\system;

use common\models\FormModel;

class AllowLoginIpSearchForm extends FormModel
{
	public $ip;
	public $ownership;

	public function rules()
	{
		return [[['ip', 'ownership'], 'safe']];
	}

	public function attributeLabels()
	{
		return ['ip' => 'IP', 'ownership' => 'IP归属'];
	}
}
