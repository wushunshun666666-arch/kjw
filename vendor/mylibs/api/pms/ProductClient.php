<?php
/**
 * ProductClient.php
 *
 * @author suxh
 */

namespace mylibs\api\pms;

use mylibs\api\JsonRpc;

class ProductClient extends JsonRpc
{
	public function __construct() {
		parent::__construct('product','pms');
	}

	/**
	 * 取得指定车型适配的所有产品ID
	 * @param integer $typeId 车型ID
	 * @param integer $year 年款
	 * @param string $displacement 排量
	 * @return false|array 成功返回所有产品ID，否则返回false
	 */
	public function getIdsByCarType($typeId, $year, $displacement) {
		$response = $this->client->call('get-ids-by-car-type', [$typeId, $year, $displacement]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}
}