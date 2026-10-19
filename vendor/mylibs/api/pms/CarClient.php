<?php
/**
 * CarClient.php
 *
 * @author suxh
 */

namespace mylibs\api\pms;

use mylibs\api\JsonRpc;

class CarClient extends JsonRpc
{
	public function __construct() {
		parent::__construct('car','pms');
	}

	/**
	 * 取得所有汽车品牌
	 * @return false|array
	 * 	id ID
	 * 	firstLetter 首字母
	 * 	name 名称
	 * 	weight 权重
	 * 	logo Logo地址
	 */
	public function getAllBrands() {
		$response = $this->client->call('get-all-brands', []);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}

	/**
	 * 取得指定品牌的车型
	 * @param integer $brandId 品牌ID
	 * @return false|array 返回所有车型信息
	 * 	id 车型ID
	 * 	name 车型名称
	 * 	seriesId 车系ID
	 * 	seriesName 车系名称
	 * 	makerId 厂家ID
	 * 	makerName 厂家名称
	 */
	public function getTypesByBrand($brandId) {
		$response = $this->client->call('get-types-by-brand', [$brandId]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}

	/**
	 * 取得指定品牌的车型
	 * @param string $keyword 关键字
	 * @return false|array 返回所有车型信息
	 * 	id 车型ID
	 * 	name 车型名称
	 * 	seriesId 车系ID
	 * 	seriesName 车系名称
	 * 	makerId 厂家ID
	 * 	makerName 厂家名称
	 */
	public function getTypesByKeyword($keyword) {
		$response = $this->client->call('get-types-by-keyword', [$keyword]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}

	/**
	 * 取得指定车型的所有年款
	 * @param integer $typeId
	 * @return false|array 返回所有年款([year1, year2, ...])
	 */
	public function getYearsByType($typeId) {
		$response = $this->client->call('get-years-by-type', [$typeId]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}

	/**
	 * 取得指定车型的所有排量
	 * @param integer $typeId 车型ID
	 * @param integer $year 年款
	 * @return false|array 返回所有排量信息([displacement1, displacement2, ...])
	 */
	public function getDisplacementsByType($typeId, $year) {
		$response = $this->client->call('get-displacements-by-type', [$typeId, $year]);

		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}
}