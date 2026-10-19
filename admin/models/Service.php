<?php
/**
 * Service.php
 *
 * @author suxh
 */

namespace app\models;
use common\models\LogicModel;

class Service extends LogicModel
{
	public function getOption($name) {
		if ($name == 'pagesize') {
			return 100;
		}
	}
}