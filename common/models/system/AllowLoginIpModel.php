<?php

namespace common\models\system;

use common\models\Error;
use common\models\LogicModel;

class AllowLoginIpModel extends LogicModel
{
	private $model;

	public function __construct(AllowLoginIp $model)
	{
		$this->model = $model;
	}

	public function getData()
	{
		return $this->model;
	}

	public static function getById($id)
	{
		$model = AllowLoginIp::findById($id);
		return $model === null ? null : new self($model);
	}

	public static function checkIp($ip, $excludeId = null)
	{
		if (AllowLoginIp::existsByIp($ip, $excludeId)) {
			return new Error('该IP地址已经存在。');
		}
		return true;
	}

	public static function create($ip, $ownership, $note)
	{
		$result = self::checkIp($ip);
		if (Error::isError($result)) {
			return $result;
		}

		$model = new AllowLoginIp();
		$model->ip = $ip;
		$model->ownership = $ownership;
		$model->note = $note;
		if (!$model->save()) {
			return new Error($model->getErrors());
		}
		return new self($model);
	}

	public function modify($ip, $ownership, $note)
	{
		$result = self::checkIp($ip, $this->model->id);
		if (Error::isError($result)) {
			return $this->addErrors($result->getErrors());
		}

		$this->model->ip = $ip;
		$this->model->ownership = $ownership;
		$this->model->note = $note;
		if (!$this->model->update(true, ['ip', 'ownership', 'note', 'update_time'])) {
			return $this->addErrors($this->model->getErrors());
		}
		return true;
	}

	public function delete()
	{
		if (!$this->model->delete()) {
			return $this->setError('删除可登录IP失败。');
		}
		return true;
	}
}
