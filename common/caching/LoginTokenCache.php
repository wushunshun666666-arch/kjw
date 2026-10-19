<?php
/**
 * 登录Token缓存
 *
 * @author suxh
 */

namespace common\caching;

use mylibs\yii\caching\AppCache;
use mylibs\security\Crypt;
use mylibs\RandString;

class LoginTokenCache extends AppCache
{
	/**
	 * 创建Token
	 * @param integer $userId 用户ID
	 * @param string $userIp 用户IP
	 * @return string 返回Token
	 */
    public function createToken($userId, $userIp=null) {
        $token = md5(RandString::complex(16));

        $data = [
        	'userId' => $userId,
        	'userIp' => $userIp,
        	'checksum' => md5($userId.$userIp.$this->key)
        ];
        $data = json_encode($data);

        $crypt = new Crypt();
        $value = $crypt->encrypt($data);
        $this->cache->set($token, $value, $this->expire);

        return $token;
    }

    /**
     * 根据Token获取授权信息
     * @param string $token
     * @return NULL|array 如果成功则返回授权信息(['userId'=>userId, 'userIp' => userIp])
     */
    public function get($token) {
    	$value = $this->cache->get($token);
        if (!$value) {
            return null;
        }

        $this->cache->delete($token);

        $crypt = new Crypt();
        $value = $crypt->decrypt($value);
        if (!$value) {
            return null;
        }
        $data = json_decode($value, true);
        if ($data === false) {
            return null;
        }
        if (!(isset($data['userId']) && isset($data['userIp']) && isset($data['checksum']))) {
            return null;
        }
        if ($data['checksum'] != md5($data['userId'].$data['userIp'].$this->key)) {
            return null;
        }

    	return $data;
    }

    private $expire = 10;
    private $key = 'Maeritn49#Mju$108UY';
}