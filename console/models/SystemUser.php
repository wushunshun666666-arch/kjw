<?php
/**
 * SystemUser.php
 *
 * @author suxh
 */

namespace app\models;

use common\models\SystemUserIdentity;

class SystemUser
{
	public function getIdentity() {
		return new SystemUserIdentity();
	}
}