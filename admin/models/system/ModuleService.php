<?php
/**
 * ModuleService.php
 */
namespace app\models\system;

use app\models\Service;
use common\models\system\Module;
use common\models\system\Category;
use common\models\system\CategoryModel;
use common\models\system\ModuleModel;
use common\models\system\RoleModule;
use yii\data\ActiveDataProvider;
use common\models\Error;


class ModuleService extends Service
{
	public function search(ModuleSearchForm $form) {
		$query = Module::find()->joinWith('category');
		$query ->filterWhere(['module.category_id'=>$form ->id]);
	
		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize'=>$this->getOption('pagesize'),
			],
			'sort' => [
				'attributes' => [
					'module.id',
				],
				'defaultOrder'=>['module.id' => SORT_DESC],
			],
		]);
	}

	public function getModuleTree(){
		return Category::getTree();	
	}

	/**
	 *$id int 是category_id 
	 */
	public function getParentName($id){
		return  CategoryModel::getName($id);
	}

	public function addCategory($name, $parentId) {
		$category = CategoryModel::create($name, $parentId);

		if (Error::isError($category)) {
			return $this->addErrors($category->getErrors());
		}

		return true;
	}

	public function add($model) {
		$url = $model->url;
		$name = $model ->name;
		$description = $model->description;
		$categoryId = $model->categoryId;

		$module = ModuleModel::create($url,$name,$description,$categoryId);

		if (Error::isError($module)) {
			return $this->addErrors($module->getErrors());
		}

		return true;
	}

	/**
	 * 模块详细信息
	 * @param integer $id
	 * @return boolean
	 */
	public function getDetail($id) {
		$model = $this->getModuleModel($id);
		if (!$model) {
			return false;
		}

		return $model->getData();
	}

	public function getCategoryDetail($id){
		$model = $this->getModel($id);
		if(!$model){
			return false;
		}
		return $model ->getData();
	}

	/**
	 * $ID为模块ID，根据模块id查找模块角色
	 * @param integer $id
	 * @return \yii\data\ActiveDataProvider
	 */
	public function getRoleModule($id){
		$query = RoleModule::find()->joinWith('role');
		$query ->Where(['role_module.module_id'=>$id]);

		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize'=>$this->getOption('pagesize'),
			],
			'sort' => [
				'attributes' => [
					'role_module.id',
				],
				'defaultOrder'=>['role_module.id' => SORT_DESC],
			],
		]);
	}

	public function modifyCategory($id,CategoryModifyForm $form){
		$model = $this->getModel($id);

		if (!$model) {
			return false;
		}

		$attributes = $form->getSafeAttributes();

		if (!$model->modify($attributes['name'],$id)) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	public function ModuleModify($id,ModuleModifyForm $form){
		$model = $this ->getModuleModel($id);

		if(!$model){
			return false;
		}
		$url = $form->url;
		$name = $form ->name;
		$description = $form->description;

		if (!$model->modify($url,$name,$description)) {
			return $this->addErrors($model->getErrors());
		}

		return true;

	}

	/**
	 * 这是删除模块类
	 * @param integer $id
	 * @return boolean
	 */
	public function delete($id){
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->delete()) {
			return $this->setError($model->getError());
		}
		return true;
	}

	/**
	 * 这是删除模块
	 * @param integer $id
	 * @return boolean
	 */
	public function deleteModule($id){
		$model = $this->getModuleModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->delete()) {
			return $this->setError($model->getError());
		}
		return true;
	}

	/**
	 * 根据ID获取类别模型模型对象
	 * @param integer $id 类别ID
	 * @return CategoryModel|false 返回类别模型
	 */
	private function getModel($id) {
		$model = CategoryModel::getById($id);
		if (!$model) {
			return $this->setError('类别不存在');
		}
		return $model;
	}

	/**
	 * 根据ID获取模块模型对象
	 * @param integer $id 模块id
	 * @return ModuleModel|false 返回模块模型 
	 */
	private function getModuleModel($id){
		$model = ModuleModel::getById($id);

		if(!$model){
			return $this->setError('模块不存在');
		}
		return $model;
	}
}