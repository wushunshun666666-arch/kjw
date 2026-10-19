<?php
/**
 * 支付请求表单
 *
 * @author suxh
 */

namespace mylibs\payment;

class RequestForm
{
	/**
	 * @var string 表单地址
	 */
	public $action;

	/**
	 * @var string 方法
	 */
	public $method = 'POST';

	/**
	 * @var array 表单字段
	 */
	public $fields = [];

	/**
	 * 设置表单字段
	 *
	 * @param string $name 字段名称
	 * @param mixed $value 字段值
	 * @return void
	 */
	public function setField($name, $value) {
		$this->fields[$name] = $value;
	}
}
