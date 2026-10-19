<?php

namespace app\models\system;

use app\models\Service;
use common\models\Error;
use common\models\system\AllowLoginIp;
use common\models\system\AllowLoginIpModel;
use yii\data\ActiveDataProvider;

class AllowLoginIpService extends Service
{
	public function search(AllowLoginIpSearchForm $form)
	{
		$query = AllowLoginIp::find()
			->andFilterWhere(['like', 'ip', $form->ip])
			->andFilterWhere(['like', 'ownership', $form->ownership]);
		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => ['pageSize' => $this->getOption('pagesize')],
			'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
		]);
	}

	public function getDetail($id)
	{
		$model = $this->getModel($id);
		return $model ? $model->getData() : false;
	}

	public function add(AllowLoginIpAddForm $form)
	{
		$model = AllowLoginIpModel::create($form->ip, $form->ownership, $form->note);
		if (Error::isError($model)) {
			return $this->addErrors($model->getErrors());
		}
		return true;
	}

	public function modify($id, AllowLoginIpModifyForm $form)
	{
		$model = $this->getModel($id);
		if (!$model || !$model->modify($form->ip, $form->ownership, $form->note)) {
			return $model ? $this->addErrors($model->getErrors()) : false;
		}
		return true;
	}

	public function delete($id)
	{
		$model = $this->getModel($id);
		if (!$model || !$model->delete()) {
			return $model ? $this->addErrors($model->getErrors()) : false;
		}
		return true;
	}

	private function getModel($id)
	{
		$model = AllowLoginIpModel::getById($id);
		if (!$model) {
			return $this->setError('可登录IP记录不存在。');
		}
		return $model;
	}
}
