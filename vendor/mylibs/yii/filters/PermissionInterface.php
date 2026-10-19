<?php
/**
 * 模块权限过滤器
 * @author suxh
 */

namespace mylibs\yii\filters;

interface PermissionInterface
{
	public function __construct($identity);
	public function hasPrivilege($route);
}