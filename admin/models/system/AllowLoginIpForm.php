<?php

namespace app\models\system;

use common\models\FormModel;

abstract class AllowLoginIpForm extends FormModel
{
	public $ip;
	public $ownership;
	public $note;

	protected function commonRules()
	{
		return [
			[['ip', 'ownership'], 'required'],
			[['ip'], 'string', 'max' => 20],
			[['ownership'], 'string', 'max' => 60],
			[['note'], 'string', 'max' => 255],
			[['ip'], 'validateIp'],
			[['ip'], 'validateUniqueIp'],
		];
	}

	public function validateIp($attribute)
	{
		$parts = explode('.', (string)$this->$attribute);
		$wildcard = false;
		if (count($parts) !== 4) {
			$this->addError($attribute, 'IP地址格式错误，可使用 192.168.1.* 或 192.168.*.*。');
			return;
		}

		foreach ($parts as $index => $part) {
			if ($part === '*') {
				$wildcard = true;
				if ($index < 2) {
					$this->addError($attribute, 'IP地址至少需要保留前两段。');
					return;
				}
				continue;
			}
			if ($wildcard || !ctype_digit($part) || (int)$part > 255 || (string)(int)$part !== $part) {
				$this->addError($attribute, 'IP地址格式错误，可使用 192.168.1.* 或 192.168.*.*。');
				return;
			}
		}
	}

	abstract public function validateUniqueIp($attribute);

	public function attributeLabels()
	{
		return ['ip' => 'IP', 'ownership' => 'IP归属', 'note' => '备注'];
	}

	public function attributeHints()
	{
		return ['ip' => '支持单个IPv4地址，或末尾通配格式：192.168.1.*、192.168.*.*。'];
	}
}
