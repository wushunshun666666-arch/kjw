<?php
/**
 * SystemUserIdentity.php
 *
 * @author suxh
 */

namespace common\models;

class SystemUserIdentity
{
	public function getType() {
		return 1000;
	}

	public function getId() {
		return 100;
	}

	public function getName() {
		return '系统账号';
	}

	public function getSysUserId() {
		return 100;
	}
}