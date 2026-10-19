<?php
/**
 *ModuleModel.php
 *
 * @author xurs
 */

namespace common\models\system;
use common\models\Error;
use common\models\LogicModel;

class ModuleModel extends LogicModel
{
	public function __construct($model) {
		$this->model = $model;
	}
	
	public function getData() {
 
		return $this->model;
	}
	
	public static function getById($id) {
		$model =  Module::findById($id);
		if ($model === null) {
			return null;
		}
		
		return new self($model);
	}
	
	/**
	 * 新增模块名称
	 * @param type $name,$categoryId,$url,$description
	 * @return \common\models\Error|\self
	 */
	public static function create($url,$name,$description,$categoryId) {
		
		$res = self::checkUrl($url);
	
		if (Error::isError($res)) {
			return $this->setError('模块URL已经存在');
		}
		
		$model = new Module();
		$model->category_id	= $categoryId;
		$model->name = $name;
		$model->url = $url;
		$model->description = $description;
		
		if (!$model->save()) {
			return new Error($model->getErrors());
		}

		return new self($model);
	}
	
	public function modify($url,$name,$description) {
		$model = $this->model;
		$id = $model->id;
		
		$res = self::checkUrl($url,$id);
		if (Error::isError($res)) {
			return $this->setError('模块URL已经存在');
		}
		
		//更新模块名称
		$model ->name = $name;
		$model ->url = $url;
		$model ->description = $description;
	
		if (!$model->save()) {
			 return $this->addErrors($model->getErrors());
		}
		
		return true;
	}
	
	/**
	 * 检查当前URL是否已经存在
	 * @param type $id是当前类别的ID
	 * @return \common\models\Error|boolean
	 */
	public static function checkUrl($url,$id=''){
		
		if(Module::extstsByUrl($url,$id)){
			return new Error('提示模块URL已经存在');
		}
		return true;
	}
	
	
	public function delete(){
		$model = $this->model;
		$id = $model->id;
	
		if(RoleModule::existsByModuleId($id)){
			return $this->setError('已有角色分配了该模块权限,不允许删除。');
		}
		if (!$model->delete()) {
			return $this->addErrors('删除失败');
		}
		
		return true;

	}
	
	protected $model;
}