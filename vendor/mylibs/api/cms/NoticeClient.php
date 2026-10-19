<?php

namespace mylibs\api\cms;

use mylibs\api\JsonRpc;

class NoticeClient extends JsonRpc
{
    public function __construct() {
         parent::__construct('notice','cms');
    }

    /**
     * 根据公告ID调用公告详情
     * @param number $limit
     */
    public function get($id) {
        $response = $this->client->call('get-by-id', $id);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

    /**
     * 获取公告列表，默认调用最新10条
     * @param number $limit
     */
    public function getLast($limit=10) {
        $response = $this->client->call('get-last', ['limit'=>$limit]);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }
}