<?php

namespace mylibs\api\admin;

use mylibs\api\JsonRpc;

class ProductClient extends JsonRpc
{
    public function __construct() {
        parent::__construct('product', 'admin');
    }

    /**
     * 根据产品编号获取产品信息
     * @param numbers 产品编号[]
     * @return \stdClass|false 返回产品信息对象
     */
    public function getByNumbers($numbers) {
        $params = [
            'numbers' => $numbers,
        ];
    	$response = $this->client->call('get-by-numbers', $params);

    	if ($response === false) {
    		return $this->setError($this->client->getError());
    	}
    	else {
    		return $response;
    	}
    }

    /**
     * 根据产品ID获取产品信息
     * @param ids 产品id[]
     * @return \stdClass|false 返回产品信息对象
     */
    public function getByIds($ids) {
        $params = [
            'ids' => $ids,
        ];
        $response = $this->client->call('get-by-ids', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }


    /**
     * 根据产品ID获取产品信息
     * @param 产品id
     * @return \stdClass|false 返回产品信息对象
     */
    public function getById($id) {
        $response = $this->client->call('get-by-id', $id);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }


    /**
     * 根据产品编号获取产品信息
     * @param  $number
     * @return \stdClass|false 返回产品信息对象
     */
    public function getByNumber($number) {
        $response = $this->client->call('get-by-number', $number);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }
}


