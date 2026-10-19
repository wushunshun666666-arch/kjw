<?php
/**
 * Resource.php
 * @author suxh
 */

namespace common\resource;

use common\models\LogicModel;
use mylibs\DateTimeUtility;

class Resource extends LogicModel
{
	protected function genDirNameByMonth() {
		return DateTimeUtility::format('now', 'Ym');
	}

    /**
     * @return string file extension
     */
    protected function getExtension($name) {
        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }
}