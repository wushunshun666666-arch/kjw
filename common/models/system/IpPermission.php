<?php
/**
 * IP权限业务模型
 * @author suxh
 */

namespace common\models\system;

use common\models\LogicModel;

class IpPermission extends LogicModel
{
	public static function isAllow($ip) {
		return AllowLoginIp::checkIp($ip);
	}
}