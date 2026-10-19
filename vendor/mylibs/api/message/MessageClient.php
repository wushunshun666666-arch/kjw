<?php
/**
 * MessageClient.php
 *
 * @author suxh
 */

namespace mylibs\api\message;

use mylibs\api\JsonRpc;

class MessageClient extends JsonRpc
{
	/**
	 * @var integer 用户类型-公司员工
	 */
    const USER_TYPE_EMPLOYEE = 1000;
	/**
	 * @var integer 用户类型-加盟商
	 */
    const USER_TYPE_DISTRIBUTION = 3000;
    /**
     * @var integer 用户类型-供应商
     */
    const USER_TYPE_SUPPLIER = 5000;

	/**
	 * @var integer 消息类型-系统通知
	 */
    const TYPE_INFO = 1000;
	/**
	 * @var integer 消息类型-系统公告
	 */
    const TYPE_NOTICE = 1001;
    /**
     * @var integer 消息类型-采购库存预警
     */
    const TYPE_EMPLOYEE_INVENTORY_WARNING = 1101;
    /**
     * @var integer 消息类型-加盟商采购预订单通知
     */
    const TYPE_EMPLOYEE_DISTRIBUTION_PURCHASE_PREORDER = 1102;

	/**
	 * @var integer 消息类型-加盟商进货单通知
	 */
    const TYPE_DISTRIBUTION_PURCHASE_ORDER = 3001;
	/**
	 * @var integer 消息类型-加盟商退货单通知
	 */
    const TYPE_DISTRIBUTION_RETURN_ORDER = 3002;
	/**
	 * @var integer 消息类型-加盟商调拨单通知
	 */
    const TYPE_DISTRIBUTION_ALLOCATION_ORDER = 3003;
    /**
	 * @var integer 消息类型-加盟商库存预警
	 */
    const TYPE_DISTRIBUTION_INVENTORY_WARNING = 3011;
    /**
     * @var integer 消息类型-加盟商到货提醒
     */
    const TYPE_DISTRIBUTION_ARRIVAL_REMINDERS = 3021;
    /**
     * @var integer 消息类型-加盟商订单通知
     */
    const TYPE_DISTRIBUTION_ORDER = 3051;

	/**
	 * @var integer 消息类型-供应商采购单通知
	 */
    const TYPE_SUPPLIER_PURCHASE_ORDER = 5001;

    /**
     * @var integer 平台类型-PC
     */
    const PLATFORM_PC = 1;
    /**
     * @var integer 平台类型-Android
     */
    const PLATFORM_ANDROID = 2;
    /**
     * @var integer 平台类型-ios
     */
    const PLATFORM_IOS = 3;

    /**
     * @var integer 消息级别-一般
     */
    const LEVEL_GENERAL = 0;
    /**
     * @var integer 消息级别-紧急
     */
    const LEVEL_EMERGENCY = 10;

    public function __construct() {
         parent::__construct('message','message');
    }

    /**
     * 发送消息
     * @param array $params
     * 	title string 消息标题
     * 	content string 消息内容
     * 	typeId integer 消息类型
     * 	level integer 消息级别，默认为0
     * 	userType integer 接收用户类型
     * 	userId integer|array 接收用户ID，为0表示该类型的所有用户
     * 	extras array 额外参数
     * 	push array 推送方式，['pc', 'mobile']
     * @return false|stdClass
     */
    public function send($params) {
        $response = $this->client->call('send', [$params]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    /**
     * 获取消息中心未读列表(包括已读和未读)
     * @param integer $userId   用户ID
     * @param integer $userType 用户类型
     * @return false|\stdClass
     */
    public function getOverview($userId,$userType) {
        $response = $this->client->call('get-overview', [$userId,$userType]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    /**
     * 取得对应消息类型的消息列表
	 * @param integer $userType 用户类型
	 * @param integer $userId   用户ID
     * @param integer $typeId   消息类型ID
	 * @param integer $page     当前请求的页码数
     * @return false|\stdClass
     */
    public function getList($userType, $userId, $typeId = null, $isOnlyUnread = false, $level = null, $page = 1, $pageSize = 10) {
        $response = $this->client->call('get-list', [$userType, $userId, $typeId, $isOnlyUnread, $level, $page, $pageSize]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    /**
     * 取得用户消息详细信息
     * @param integer $id 消息ID
     * @param integer $userType 用户类型
     * @param integer $userId 用户ID
     * @return false|\stdClass
     */
    public function getDetail($id, $userType, $userId) {
    	$response = $this->client->call('get-detail', [$id, $userType, $userId]);

    	if ($response === false) {
    		return $this->setError($this->client->getError());
    	}

    	return $response;
    }

    /**
     * 阅读消息
     * @param integer $id 消息ID
     * @param integer $userType 用户类型
     * @param integer $userId 用户ID
     * @param integer $platform 平台
     * @return boolean
     */
    public function read($id, $userType, $userId, $platform) {
    	$response = $this->client->call('read', [$id, $userType, $userId, $platform]);

        if ($response === false) {
    		return $this->setError($this->client->getError());
    	}

    	return $response;
    }

    public function batchMarkRead($userId, $userType, $platform, $ids = '') {
        $response = $this->client->call('batch-mark-read', [$userId, $userType, $platform, $ids]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    public function getNewNums($userId, $userType, $typeId = null) {
        $response = $this->client->call('get-new-nums', [$userId, $userType, $typeId]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    public function getTypeNames($userType=null) {
        $response = $this->client->call('get-type-names', [$userType]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    /**
     * 注册push
     * @param integer $userId   用户ID
     * @param integer $userType 用户类型
     * @param string  $registrationId pushID
     * @return false|\stdClass
     */
    public function registerPush($userId, $userType, $registrationId) {
        $response = $this->client->call('register-push', [$userId, $userType, $registrationId]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }

    /**
     * 注销push
     * @param integer $userId   用户ID
     * @param integer $userType 用户类型
     * @return false|\stdClass
     */
    public function unregisterPush($userId, $userType) {
        $response = $this->client->call('unregister-push', [$userId, $userType]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }

        return $response;
    }
}