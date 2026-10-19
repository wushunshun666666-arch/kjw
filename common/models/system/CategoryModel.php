<?php
/**
 *CategoryModel.php
 *
 * @author xurs
 */

namespace common\models\system;
use common\models\Error;
use common\models\LogicModel;

class CategoryModel extends LogicModel
{
	public function __construct($model) {
		$this->model = $model;
	}
	
	public function getData() {
 
		return $this->model;
	}
	
	public static function getById($id) {
		$model =  Category::findById($id);
		if ($model === null) {
			return null;
		}
		
		return new self($model);
	}
	
	public static function getTree($root = null) {
		return Category::getTree($root);
	}
	
	public static function getTreeList($root = null) {
		$trees = Category::getTree($root);
		
		$list = [];
		self::buildTreeList($list, $trees);
		return $list;
	}
	
	private static function buildTreeList(&$list, $trees) {
		foreach ($trees as $tree) {
			$id = $tree['id'];
			$level = $tree['level'];
			$list[$id] = str_repeat('　', $level).$tree['name'];
			if (!empty($tree['children'])) {
				self::buildTreeList($list, $tree['children']);
			}
		}
	}
	
	/**
	 * 获取当前父类名称
	 * @return null
	 */
	public static function getName($id){
		$model =  Category::findParentName($id);
		if ($model === null) {
			return null;
		}
		 
		return $model;
	}
	
	/**
	 * 检查名称是否存在
	 * @param type $name,$id是当前类别的父类ID
	 * @return \common\models\Error|boolean
	 */
	public static function checkName($name,$id) {
		
		if (Category::extstsByName($name,$id)) {
			return new Error('类别名称已经存在。');
		}
		return true;
	}
	
	/**
	 *  验证新增类别的级别是否合法(最多只能3级)
	 * @param type $id 是当前类别的父类ID
	 * @return \common\models\Error|boolean
	 */
	public static function checkLevel($id){
	
		if(Category::biggerLevel($id)){
			return new Error('级别错误');
		}
		return true;
	}
	
	/**
	 * 检查当前类别是否为系统预留类别
	 * @param type $id是当前类别的ID
	 * @return \common\models\Error|boolean
	 */
	public static function checkStatus($id){
		
		if(Category::extstsByStatus($id)){
			return new Error('系统预留类别，不能修改');
		}
		return true;
	}
	
	/**
	 * 父类ID，新增类别名称
	 * @param type $name,$parentId
	 * @return \common\models\Error|\self
	 */
	public static function create($name,$parentId) {
		$level = Category::findLevel($parentId);
		
		if($level>=Category::Level3){
			return new Error('级别错误');
		}
		
		$resName = self::checkName($name, $parentId);
		if (Error::isError($resName)) {
			return $resName;
		}
		
		$model = new Category();
		$model->name		= $name;
		$model->parent_id 	= $parentId;
		$model['level'] = $level + 1;
		$model['type'] = Category::TYPE_1000;
		$model['orderby'] = Category::OrderBy;
		$model['status'] = Category::Status_0;
		if (!$model->save()) {
			return new Error($model->getErrors());
		}

		return new self($model);
	}
	
	/**
	 * 
	 * @param type $name 当前需要修改的新类别名
	 * @param type $id 当前类别ID
	 * @return \common\models\Error|boolean
	 */
	public function modify($name,$id) {
		$status = Category::findStatus($id);
		$res = self::checkStatus($status['status']);
	
		if (Error::isError($res)) {
			return $this->setError('系统预留类别，不能修改');
		}
		
		$resName = self::checkName($name, $status['parent_id']);
		
		if (Error::isError($resName)) {
			return $resName;
		}
		
		$resLevel = self::checkLevel($status['parent_id']);
		
		if (Error::isError($resLevel)) {
			return $resLevel;
		}
		
		//更新类别名称
		$model = $this->model;
		$model -> name 	    = $name;
		$model -> type 		= $status['type'];
		$model -> parent_id = $status['parent_id'];
		$model -> level		= $status['level'];
		$model -> orderby   =$status['orderby'];
		$model -> status    =$status['status'];
		
		if ($model->update() === false ) {
			return new Error($model->getErrors());
		}
		
		return true;
	}
	
	public function delete(){
		$model = $this->model;
		
		$res = self::checkStatus($model['status']);
	
		if (Error::isError($res)) {
			return $this->setError('系统预留类别，不能删除');
		}
		
		if($model['level'] < Category::Level3){
			if(Category::existsByParentId($model->id)){
				return $this->setError('包含下级类别，不能删除');
			}
		}
		
		if(Category::existsByModule($model->id)){
			return $this->setError('类别下含有模块，不能删除');
		}
		  
		if (!$model->delete()) {
			return $this->addErrors('删除失败');
		}
		
		return true;
		
	}
	
	private $model;
}