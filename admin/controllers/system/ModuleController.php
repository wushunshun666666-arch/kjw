<?php
/**
 * ModuleController.php
 * @author xurs
 */

namespace app\controllers\system;

use app\controllers\Controller;
use yii\web\HttpException;
use app\models\system\ModuleSearchForm;
use app\models\system\ModuleAddForm;
use app\models\system\ModuleModifyForm;
use app\models\system\CategoryAddForm;
use app\models\system\CategoryModifyForm;
use app\models\system\ModuleService;
use common\models\system\Module;
use app\helpers\Url;


class ModuleController extends Controller
{
	protected function withoutAuthorization() {
		return [
			'check-add-category',
			'check-modify-category',
			'check-url',
			'check-module-url'
		];
	}

	public function actionIndex() {
		$model = new ModuleSearchForm();
		$service = new ModuleService();

		return $this->render('index', [
			'model' => $model,
			'module' => $service->getModuleTree(),
		]);
	}

	public function actionList() {
		$model = new  ModuleSearchForm();
		$model->loadPost();

		if (!$model->validate()) {
			return $this->jsonError($model->getError());
		}

		$service = new ModuleService();

		$dataProvider = $service->search($model);

		return $this->jsonTable('_view_list', $dataProvider);

	}

	/**
	 * 新增模块类别
	 */
	public function actionAddCategory(){
		$model = new CategoryAddForm();
		$service = new ModuleService();

		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}
			if (!$service->addCategory($model->name, $model->parentId)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('新增模块类别成功。', ['url'=>Url::to(['index'])]);
		} else {
			$model->parentId = $this->getId();
			return $this->renderModal('add_category', [
				'model' => $model,
				'parentName' => $service->getParentName($model->parentId),
			]);
		}
	}

	/**
	 * 新增模块类别检查模块类别名称
	 */
	public function actionCheckAddCategory(){
		$model = new CategoryAddForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['name','id']);
		}
	}

	/**
	 * 修改模块类别
	 */
	public function actionModifyCategory(){
		$id = $this->getId();

		$model = new CategoryModifyForm();
		$service = new ModuleService();

		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->modifyCategory($id, $model)) {
				return $this->jsonError($service->getError());
			}

			return $this->jsonSuccess('类别修改成功。', ['url'=>Url::to(['index'])]);
		} else {
			$data = $service->getCategoryDetail($id);

			if (!$data) {
				return $this->renderError($service->getError());
			}
			$model->load($data->attributes, '');

			return $this->renderModal('modify_category', [
				'model' => $model,
			]);
		}
	}

	public function actionCheckModifyCategory() {
		$model = new CategoryModifyForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['name']);
		}
	}

	/**
	 * 删除模块类别
	 */
	public function actionDeleteCategory(){
		$id = \Yii::$app->request->post('id');
		$service = new ModuleService();
		if(!$service->delete($id)){
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除成功。');
	}

	/**
	 * 查看详细信息
	 */
	public function actionDetail(){
		$id = $this->getId();

		$service = new ModuleService();
		$model = $service->getDetail($id);

		if (!$model) {
			return $this->renderError($service->getError());
		}

		return $this->render('detail', ['model'=> $model,]);
	}

	/**
	 * 查看详细信息(模块角色列表)
	 */
	public function actionRoleModule(){
		$id = $this->getId();
		$service = new ModuleService();

		$data = $service->getRoleModule($id);

		if (!$data) {
			return $this->renderError($service->getError());
		}

		return $this->jsonTable('_view_role_module', $data);
	}

	/**
	 * 新增模块
	 */
	public function actionAdd(){
		$model = new ModuleAddForm();
		$service = new ModuleService();

		$model->categoryId = $this->getId();

		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->add($model)) {
				return $this->jsonError($service->getError());
			}
			return $this->jsonSuccess('新增模块成功。', ['url'=>Url::to(['index'])]);
		} else {
			$model->categoryId = $this->getId();
			return $this->renderModal('add', [
				'model' => $model,
				'categoryName' => $service->getParentName($model->categoryId),
			]);
		}

	}

	/**
	 * 删除模块
	 */
	public function actionDelete(){
		$id = $this->getId();
		$service = new ModuleService();
		if(!$service->deleteModule($id)){
			return $this->jsonError($service->getError());
		}
		return $this->jsonSuccess('删除成功。');
	}

	/**
	 * 修改模块
	 */
	public function actionModify(){
		$id = $this->getId();
		$model = new ModuleModifyForm();
		$service = new ModuleService();

		if ($model->loadPost()) {
			if (!$model->validate()) {
				return $this->jsonError($model->getError());
			}

			if (!$service->ModuleModify($id, $model)) {
				return $this->jsonError($service->getError());
			}

			return $this->jsonSuccess('模块修改成功。', ['url'=>Url::to(['index'])]);
		} else {
			$data = $service->getDetail($id);

			if (!$data) {
				return $this->renderError($service->getError());
			}

			$model->load($data->attributes, '');

			return $this->render('modify', [
				'model' => $model,
				'categoryName' =>$service ->getCategoryDetail($data['category_id']),
			]);
		}
	}

	/**
	 * 添加模块时候的验证URL
	 */
	public function actionCheckUrl(){
		$model = new ModuleAddForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['url']);
		}
	}

	/**
	 * 修改模块的时候验证URL
	 */
	public function actionCheckModuleUrl(){
		$model = new ModuleModifyForm();
		if ($model->loadPost()) {
			return $this->renderValidate($model, ['url']);
		}
	}

	private function getId() {
		$id = intval(\Yii::$app->request->get('id', 0));
		if ($id < 1) {
			throw new HttpException(404);
		}
		return $id;
	}

}