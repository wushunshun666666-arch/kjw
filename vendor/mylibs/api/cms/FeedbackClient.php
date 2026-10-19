<?php

namespace mylibs\api\cms;

use mylibs\api\JsonRpc;

class FeedbackClient extends JsonRpc
{
	/**
	 * @var integer 平台-管理后台
	 */
	const PLATFORM_ADMIN_PC = 1000;
	/**
	 * @var integer 平台-仓库PC
	 */
	const PLATFORM_WAREHOUSE_PC = 2000;
	/**
	 * @var integer 平台-加盟商PC
	 */
	const PLATFORM_DISTRIBUTION_PC = 3000;
	/**
	 * @var integer 平台-加盟商APP
	 */
	const PLATFORM_DISTRIBUTION_APP = 3001;
	/**
	 * @var integer 平台-修理厂APP
	 */
	const PLATFORM_GARAGE_APP = 4001;

    public function __construct() {
         parent::__construct('feedback','cms');
    }

    /**
     * 添加cms里的反馈表信息
     * @param string $content
     */
    public function add($title,$content,$userId,$userName,$platform) {
    	$params = [
    		'title'=>$title,
    		'content'=>$content,
    		'userId'=>$userId,
    		'userName'=>$userName,
    		'platform'=>$platform
    	];
        $response = $this->client->call('add', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }
}


