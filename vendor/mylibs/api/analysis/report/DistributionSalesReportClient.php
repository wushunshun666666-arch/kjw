<?php

namespace mylibs\api\analysis\report;

use mylibs\api\JsonRpc;

class DistributionSalesReportClient extends JsonRpc
{
    public function __construct() {
        parent::__construct('report/distribution/sales', 'analysis');
    }

    /**
     * 取得按用户分类的统计列表
     * @param integer $distributionId 加盟商ID
     * @param integer $startMonth 起始月份
     * @param integer $endMonth 结束月份
     * @return array|false 返回列表
     */
    public function getUserList($distributionId, $startMonth=null, $endMonth=null) {
    	$startMonth = ($startMonth) ?: null;
    	$endMonth = ($endMonth) ?: null;
    	$params = [$distributionId, $startMonth, $endMonth];
    	$response = $this->client->call('get-user-list', $params);

    	if ($response === false) {
    		return $this->setError($this->client->getError());
    	}
    	else {
    		return $response;
    	}
    }

    /**
     * 取得时间内销售的总金额和总数量
     * @param integer $distributionId 加盟商ID
     * @param integer $startMonth 起始月份
     * @param integer $endMonth 结束月份
     * @return array|false 返回列表
     */

    public function getUserTotal($distributionId, $startMonth=null, $endMonth=null) {
    	$startMonth = ($startMonth) ?: null;
    	$endMonth = ($endMonth) ?: null;
        $params = [$distributionId, $startMonth, $endMonth];
        $response = $this->client->call('get-user-total', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

    /**
     * 取得每月所有业务员销售总量和金额
     * @param integer $distributionId 加盟商ID
     * @param integer $startMonth 起始月份
     * @param integer $endMonth 结束月份
     * @return array|false 返回列表
     */

    public function getUserTotalByMonth($distributionId, $startMonth=null, $endMonth=null) {
    	$startMonth = ($startMonth) ?: null;
    	$endMonth = ($endMonth) ?: null;
        $params = [$distributionId, $startMonth, $endMonth];
        $response = $this->client->call('get-user-total-by-month', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

    /**
     * 取得每个业务员每月销售量和金额
     * @param integer $distributionId 加盟商ID
     * @param integer $startMonth 起始月份
     * @param integer $endMonth 结束月份
     * @return array|false 返回列表
     */

    public function getUserListByMonth($distributionId, $startMonth=null, $endMonth=null) {
    	$startMonth = ($startMonth) ?: null;
    	$endMonth = ($endMonth) ?: null;
        $params = [$distributionId, $startMonth, $endMonth];
        $response = $this->client->call('get-user-list-by-month', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

	/**
	 * 取得按客户分类的统计列表
	 * @param array $params
	 * 	distributionId integer 加盟商ID，必填项
	 * 	startMonth integer 起始月份
	 * 	endMonth integer 结束月份
	 * 	customerId integer 客户ID
	 * 	page integer 页数
	 * 	pageSize integer 每页记录数
	 * @return array|false 返回列表
	 */
	public function getCustomerList($params) {
		$response = $this->client->call('get-customer-list', $params);
		if ($response === false) {
			return $this->setError($this->client->getError());
		}
		else {
			return $response;
		}
	}

	/**
	 * 取得客户分类统计详情明细
	 * @param array $params
	 *  distributionId integer 加盟商ID  必填项
	 *	customerId  integer 客户ID  必填项
	 *	startMonth  integer 开始月份
	 *	endMonth  integer  结束月份
	 */
	public function getCustomerItem($params){
		$response = $this->client->call('get-customer-item', $params);
		if ($response === false) {
			return $this->setError($this->client->getError());
		}

		return $response;
	}

	/**
	 * 取得按收款分类统计数据
	 * distributionId integer 加盟商ID，必填项
	 * startMonth integer 起始月份
	 * endMonth integer 结束月份
	 * @return array|false
	 */
	public function getPaymentMethodList($distributionId,$startMonth=null, $endMonth=null){
		$startMonth = ($startMonth) ?: null;
		$endMonth = ($endMonth) ?: null;
		$params = [$distributionId, $startMonth, $endMonth];
		$response = $this->client->call('get-payment-method-list', $params);
		if ($response === false) {
			return $this->setError($this->client->getError());
		}

		return $response;
	}
}