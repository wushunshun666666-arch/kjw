<?php
/**
 * Department.php
 * @author wudf
 */
namespace app\models\system;

use app\models\Service;
use common\models\Error;
use app\models\system\DepartmentForm;
use common\models\system\DepartmentModel;

class DepartmentService extends Service
{
	/**
	 * 新增部门
	 */
	public function add(DepartmentForm $form) {
		$model = DepartmentModel::create($form->id,$form->name);
		if (Error::isError( $model)) {
			return $this->addErrors( $model->getErrors());
		}
		return true;
	}

	/**
	 * 修改部门
	 */
	public function modify($id,DepartmentForm $form) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->modify($form->name)) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	/**
	 * 获取部门列表
	 */
	public function getDepartments() {
		return DepartmentModel::getTreeList();
	}

	/**
	 * 获取父节点部门ID
	 */
	public function getFatherid($id) {
		$fatherid = $this->getDetail($id);
		return $fatherid->parent_id;
	}

	/**
	 * 根据部门ID获取部门名称
	 */
	public function getDepartmentName($id) {
		$department = $this->getDetail($id);
		if(empty($department['name'])){
			$departmentname = '顶级部门';
		} else {
			$departmentname = $department ['name'];
		}
		return $departmentname;
	}

	/**
	 * 删除指定ID的部门数据
	 */
	public function delete($id) {
		$model = $this->getModel($id);
		if (! $model) {
			return false;
		}

		if (!$model->delete()) {
			return $this->addErrors($model->getErrors());
		}
		return true;
	}

	/**
	 * 取部门详细信息
	 * 
	 */
	public function getDetail($id) {
		$department = $this->getModel($id);
		if (!$department) {
			return false;
		}

		return $department->getData();
	}
	/**
	 * 根据ID获取部门模型对象
	 * 
	 */
	private function getModel($id) {
		$department = DepartmentModel::getById($id);
		if (!$department) {
			return $this->setError('部门不存在');
		}

		return $department;
	}

}