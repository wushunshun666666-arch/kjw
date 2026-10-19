<?php
/**
 * 用户管理
 *
 * @author suxh
 */

namespace app\controllers\system;

use app\controllers\Controller;
use app\models\system\UserService;
use app\models\system\UserSearchForm;
use app\models\system\UserModifyForm;
use app\models\system\UserAddForm;
use app\models\system\DepartmentForm;
use app\models\system\DepartmentService;
use app\helpers\Url;
use yii\web\HttpException;

class UserController extends Controller
{
    protected function withoutAuthorization() {
        return ['check-add','check-name'];
    }

	public function actionIndex() {
		$model = new UserSearchForm();

		$service = new UserService();

		return $this->render('index', [
			'model' => $model,
			'departments' => $service->getDepartmentTree(),
			'statusNames' => $service->getStatusNames(),
		]);
	}

	public function actionList() {
		$model = new UserSearchForm();
		$model->loadPost();

		$service = new UserService();
		$dataProvider = $service->search($model);

		return $this->jsonTable('_view_list', $dataProvider);
	}

	public function actionCheckAdd() {
		$model = new UserAddForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['username']);
		}
	}

	public function actionAdd() {
		$service = new UserService();
		$model = new UserAddForm();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->add($model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('新增用户成功。', ['url'=>Url::to(['index'])]);
		}
		else {
			return $this->render('add', [
				'model' => $model,
				'departments' => $service->getDepartments(),
			]);
		}
	}

	public function actionDetail() {
		$id = $this->getId();

		$service = new UserService();
		$model = $service->getDetail($id);
		if (!$model) {
			return $this->renderError($service->getError());
		}

		return $this->render('detail', [
			'model'=> $model,
		]);
	}

	public function actionModify() {
		$id = $this->getId();

		$model = new UserModifyForm();
		$service = new UserService();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->modify($id, $model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('修改成功', ['url'=>Url::to(['index'])]);
		}
		else {
			$user = $service->getDetail($id);
			if (!$user) {
				return $this->renderError($service->getError());
			}
			$model->load($user->attributes, '');

			return $this->render('modify', [
				'model' => $model,
				'departments' => $service->getDepartments(),
			]);
		}
	}

	public function actionResetPassword() {
		$id = $this->getId();

		$service = new UserService();
		$password = $service->resetPassword($id);
		if (!$password) {
			return $this->jsonError($service->getError());
		}

		return $this->jsonSuccess('重置用户密码成功，新的密码：'.$password);
	}

	public function actionSetPermission() {
		$id = $this->getId();
		$service = new UserService();
		$submit = \Yii::$app->request->post('submit', false);
		if ($submit) {
			$roles = \Yii::$app->request->post('roles', []);
			if (!is_array($roles)) {
				return $this->jsonError('请选择角色。');
			}
			if (!$service->setPermission($id, $roles)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('设置用户权限成功。', [
				'url'=>Url::to(['index'])
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

	/**
	 * 注销用户
	 */
	public function actionClose() {
		$id = $this->getId();

		$service = new UserService();
		if (!$service->close($id)) {
			return $this->jsonError($service->getError());
		}

		return $this->jsonSuccess('注销用户成功');
	}

	private function getId() {
		$id = intval(\Yii::$app->request->get('id', 0));
		if ($id < 1) {
			throw new HttpException(404);
		}

		return $id;
	}

	/**
	 * AJAX判断同级是否存在同名部门
	 */
	public function actionCheckName() {
	    $model = new DepartmentForm();
	    if ($model->loadPost()) {
	        return $this->renderValidate($model, ['name']);
	    }
	}

	/**
	 * 新增部门
	 */
	public function actionAddDepartment() {

	    $model = new DepartmentForm();
	    $service = new DepartmentService();
	    if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->add($model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('新增部门成功。', ['url'=>Url::to(['index'])]);
		}
		else{
		    $id = $this->getId();
		    $departmentname = $service->getDepartmentName($id);
		    $model->id = $id;
			return $this->render('add_department', [
				'model' => $model,
				'departmentname' => $departmentname,
			]);
		}

	}

	/**
	 * 修改编辑部门
	 */
	public function actionModifyDepartment() {
		$id = $this->getId();
		$model = new DepartmentForm();
		$service = new DepartmentService();
		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->modify($id, $model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('修改成功', ['url'=>Url::to(['index'])]);
		}
		else {
			$user = $service->getDetail($id);
			if (!$user) {
				return $this->renderError($service->getError());
			}

			$fatherid = $service->getFatherid($id);
			$departmentname = $service->getDepartmentName($fatherid);

			$model->load($user->attributes, '');
			$model->id = $fatherid;
			return $this->render('edit_department', [
				'model' => $model,
				'departmentname' => $departmentname
			]);
		}
	}

	/**
	 * 删除指定ID部门
	 */
	public function actionDelDepartment() {
		$model = new DepartmentForm ();
		$service = new DepartmentService();
		$id = intval(\Yii::$app->request->post('id', 0));
		if ($id < 1) {
			throw new HttpException(404);
		}
		if(!$service->delete($id)){
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除部门成功。', ['url'=>Url::to(['index'])]);
	}
}