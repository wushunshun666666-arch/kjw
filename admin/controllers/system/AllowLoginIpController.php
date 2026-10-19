<?php

namespace app\controllers\system;

use app\controllers\Controller;
use app\helpers\Url;
use app\models\system\AllowLoginIpAddForm;
use app\models\system\AllowLoginIpModifyForm;
use app\models\system\AllowLoginIpSearchForm;
use app\models\system\AllowLoginIpService;
use yii\web\HttpException;

class AllowLoginIpController extends Controller
{
	protected function withoutAuthorization()
	{
		return ['check-add', 'check-modify'];
	}

	protected function verbFilter()
	{
		return [
			'list' => ['POST'],
			'check-add' => ['POST'],
			'check-modify' => ['POST'],
			'delete' => ['POST'],
		];
	}

	public function actionIndex()
	{
		return $this->render('index', ['model' => new AllowLoginIpSearchForm()]);
	}

	public function actionList()
	{
		$model = new AllowLoginIpSearchForm();
		$model->loadPost();
		if (!$model->validate()) {
			return $this->jsonError($model->getError());
		}
		$service = new AllowLoginIpService();
		return $this->jsonTable('_view_list', $service->search($model));
	}

	public function actionCheckAdd()
	{
		$model = new AllowLoginIpAddForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['ip']);
		}
	}

	public function actionCheckModify()
	{
		$model = new AllowLoginIpModifyForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['ip']);
		}
	}

	public function actionAdd()
	{
		$model = new AllowLoginIpAddForm();
		if (!$model->loadPost()) {
			return $this->render('add', ['model' => $model]);
		}
		if (!$model->validate()) {
			return $this->jsonError($model->getError());
		}
		$service = new AllowLoginIpService();
		if (!$service->add($model)) {
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('新增可登录IP成功。', ['url' => Url::to(['index'])]);
	}

	public function actionModify()
	{
		$id = $this->getId();
		$service = new AllowLoginIpService();
		$model = new AllowLoginIpModifyForm();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}
			if (!$service->modify($id, $model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('修改可登录IP成功。', ['url' => Url::to(['index'])]);
		}

		$data = $service->getDetail($id);
		if (!$data) {
			return $this->renderError($service->getError());
		}
		$model->load($data->attributes, '');
		return $this->render('modify', ['model' => $model]);
	}

	public function actionDelete()
	{
		$service = new AllowLoginIpService();
		if (!$service->delete($this->getId())) {
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除可登录IP成功。');
	}

	private function getId()
	{
		$id = (int)\Yii::$app->request->get('id', 0);
		if ($id < 1) {
			throw new HttpException(404);
		}
		return $id;
	}
}
