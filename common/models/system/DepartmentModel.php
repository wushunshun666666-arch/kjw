<?php
/**
 * DepartmentModel.php
 *
 * @author suxh
 */

namespace common\models\system;

use common\models\Error;
use common\models\LogicModel;
use common\models\system\Department;


class DepartmentModel extends LogicModel
{
	public function __construct($model) {
		$this->model = $model;
	}
	
	public function getData() {
		return $this->model;
	}
	
	public static function getById($id) {
		$model = Department::findOne($id);
		if ($model === null) {
			return null;
		}
		
		return new self($model);
	}
	
	public static function getTree($root = null) {
		return Department::getTree($root);
	}
	
	public static function getTreeList($root = null) {
		$trees = Department::getTree($root);

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
	 * 检查部门名称是否可用
	 * 同一部门下的子部门不能重名
	 * @param integer $parentId 父级部门ID
	 * @param string $name 名称
	 * @return \common\models\Error|boolean
	 */
	public static function checkName($parentId, $name) {
	    $result = Department::find()->where(['name'=>$name,'parent_id'=>$parentId])->exists();
	    if ($result) {
	        return new Error('部门名已经存在。');
	    }

	    return true;
	}

	/**
	 * 创建部门
	 * @param integer $parentId 父级部门ID
	 * @param string $name 名称
	 * @return Error|DepartmentModel
	 */
	public static function create($parentId, $name) {    
	    $model = new Department();
	    $model->parent_id	= $parentId;
	    $model->name 		= $name;
	    $model->status		= Department::STATUS_1000;

	    if (!$model->save()) {
	        return new Error($model->getErrors());
	    }
	    
	    return new self($model);
	}

	/**
	 * 修改部门
	 * @param string $name 名称
	 * @return boolean 返回操作是否成功
	 */
     public function modify($name) {
		$model = $this->model;
        $model->name = $name;
		if (!$model->save()) {
			return $this->addErrors($model->getErrors());
		}
		return true;
	}

	/**
	 * 删除部门
	 * @return boolean 返回操作是否成功
	 */
	public function delete() {
	    $model = $this->model;
	    // 顶级部门不能删除
	    if($model->parent_id==0){
			return $this->setError('顶级部门不能删除');
	    }

		// 包含了子部门不能删除
	    $hasson = Department::find()->where(['parent_id'=>$model->id])->exists();
	    if($hasson){
			return $this->setError('包含下级部门，不能删除。');
	    }

	    if(!$model->delete()){
	        return $this->setError('删除失败');
	    }
	    
	    return true;
	}
	
	/**
	 * @var Department
	 */
	private $model;
}