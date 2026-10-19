<?php

namespace mylibs\api\admin;

use mylibs\api\JsonRpc;
use mylibs\security\Crypt;

class UserClient extends JsonRpc
{
    public function __construct() {
        parent::__construct('user', 'admin');
    }

    /**
     * 验证是否可以登录
     * @param string $username 用户名
     * @param string $password 密码
     * @return \stdClass|false 返回用户信息对象
     */
    public function validateLogin($username, $password) {
    	$crypt = new Crypt();

    	$params = [
    		'username' => $username,
    		'password' => $crypt->encrypt($password),
    	];
    	$response = $this->client->call('validate-login', $params);

    	if ($response === false) {
    		return $this->setError($this->client->getError());
    	}
    	else {
    		return $response;
    	}
    }

    /**
     * 根据用户ID取得用户信息
     * @param integer $id 用户ID
     * @return \stdClass|false 返回用户信息对象
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
     * 根据用户名取得用户信息
     * @param string $username 用户名
     * @return \stdClass|false 返回用户信息对象
     */
    public function getByUsername($username) {
        $response = $this->client->call('get-by-username', $username);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

    public function getList() {
    	$response = $this->client->call('get-list', []);

    	if ($response === false) {
    		return $this->setError($this->client->getError());
    	}
    	else {
    		return $response;
    	}
    }
}


