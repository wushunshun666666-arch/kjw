<?php
/**
 * RoleController.php
 * @author suxh
 */

namespace app\controllers\system;

use app\controllers\Controller;
use app\models\system\RoleService;
use app\models\system\RoleSearchForm;
use app\models\system\RoleAddForm;
use app\models\system\RoleModifyForm;
use app\helpers\Url;
use common\models\system\User;
use yii\web\HttpException;

class RoleController extends Controller
{
    protected function withoutAuthorization() {
        return ['check-add', 'check-modify'];
    }

	public function actionIndex() {
		$model = new RoleSearchForm();

		$service = new RoleService();

		return $this->render('index', [
			'model' => $model,
		]);
	}

	public function actionList() {
		$model = new RoleSearchForm();
		$model->loadPost();

		if (!$model->validate()) {
			return $this->jsonError($model->getError());
		}

		$service = new RoleService();
		$dataProvider = $service->search($model);
		return $this->jsonTable('_view_list', $dataProvider);
	}

    /**
	 * 查看此权限下的所有用户
	 */
	public function actionUser(){
		$id = $this->getId();
		$service = new RoleService();
		$model = new  User();

		return $this->render('role_user',[
			'model'=>$model,
			'rolename' =>$service->getDetail($id),
			]);
	}

	/**
	 * 权限下所有用户列表
	 */
    public function actionUserList(){
        $id = $this->getId();
        $service = new RoleService();
        $dataProvider = $service->getRoleUser($id);
       return $this->jsonTable('_view_list_role_user', $dataProvider);
    }

    /**
	 * 删除该权限下的指定用户
	 */
    function actionDeleteUser(){
        $id = $this->getId();
        $userId = $this->getUserId();

        $service = new RoleService();
        if(!$service->deleteUser($id, $userId)){
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除用户成功。');
    }

	/**
	 * 删除角色
	 */
	function actionDelRole(){
		$id = $this->getId();
		$service = new RoleService();
		if(!$service->deleteRole($id)){
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除角色成功。');
	}

	public function actionCheckAdd() {
		$model = new RoleAddForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['name']);
		}
	}

	public function actionCheckModify() {
		$model = new RoleModifyForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['name']);
		}
	}

	public function actionAdd() {
		$model = new RoleAddForm();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			$service = new RoleService();
			if (!$service->add($model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('新增角色成功。', ['url'=>Url::to(['index'])]);
		}
		else {
			return $this->render('add', [
				'model' => $model,
			]);
		}
	}

	public function actionModify() {
		$id = $this->getId();

		$service = new RoleService();
		$model = new RoleModifyForm();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->modify($id, $model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('修改角色成功。', ['url'=>Url::to(['index'])]);
		}
		else {
			$role = $service->getDetail($id);
			if (!$role) {
				return $this->renderError($service->getError());
			}
			$model->load($role->attributes, '');

			return $this->render('modify', [
				'model' => $model,
			]);
		}
	}

	public function actionViewPermission() {
		$id = $this->getId();
		$service = new RoleService();
		$data = $service->getPermission($id);
		if (!$data) {
			return $this->renderError($service->getError());
		}

		$data['backurl'] = Url::to(['index']);

		return $this->render('view_permission', $data);
	}

	public function actionSetPermission() {
		$id = $this->getId();

		$service = new RoleService();
		$submit = \Yii::$app->request->post('submit', false);
		if ($submit) {
			$modules = \Yii::$app->request->post('modules', []);
			if (!is_array($modules)) {
				return $this->jsonError('请选择模块。');
			}
			if (!$service->setPermission($id, $modules)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('设置角色权限成功。', [
				'url'=>Url::to(['view-permission', 'id'=>$id])
			]);
		}
		else {
			$data = $service->getPermission($id);
			if (!$data) {
				return $this->renderError($service->getError());
			}

			return $this->render('set_permission', $data);
		}
	}

	private function  getId() {
		$id = intval(\Yii::$app->request->get('id', 0));
		if ($id < 1) {
			throw new HttpException(404);
		}

		return $id;
	}

	private function getUserId() {
		$id = intval(\Yii::$app->request->get('user_id', 0));
		if ($id < 1) {
			throw new HttpException(404);
		}

		return $id;
	}
}