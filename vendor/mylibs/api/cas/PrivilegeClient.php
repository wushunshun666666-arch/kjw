<?php
/**
 * PrivilegeClient.php
 *
 * @author suxh
 */

namespace mylibs\api\cas;

use mylibs\api\JsonRpc;

class PrivilegeClient extends JsonRpc
{
    public function __construct() {
         parent::__construct('privilege','cas');
    }

    /**
     * 取得用户拥有权限的所有路由
     * @param integer $userId
     * @param integer $appId
     */
    public function getRoutesOfUser($userId, $appId) {
    	$params = [$userId, $appId];
        $response = $this->client->call('get-routes-of-user', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }

    /**
     * 判定用户是否拥有指定路由的权限
     * @param integer $userId
     * @param integer $appId
     * @param String $route
     */
    public function hasPrivilege($userId, $appId, $route) {
    	$params = [$userId, $appId, $route];
        $response = $this->client->call('has-privilege', $params);

        if ($response === false) {
            return $this->setError($this->client->getError());
        }
        else {
            return $response;
        }
    }
}