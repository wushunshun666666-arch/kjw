<?php
/**
 * 登录统计缓存
 *
 * @author suxh
 */

namespace common\caching;

use mylibs\yii\caching\AppCache;

class LoginCache extends AppCache
{
    /**
     * 计数
     * @param string $ip 登录IP
     * @return integer 返回当前计数
     */
    public function count($ip = null) {
    	if ($ip == null) {
    		$ip = \Yii::$app->request->getUserIP();
    	}

        $count = $this->get($ip);
        ++$count;
        $this->cache->set($ip, $count, $this->expire);

        return $count;
    }

    public function get($ip = null) {
    	if ($ip == null) {
    		$ip = \Yii::$app->request->getUserIP();
    	}

    	$count = $this->cache->get($ip);
    	return intval($count);
    }

    /**
     * 重置计数
     * @param string $ip 登录IP
     */
    public function reset($ip = null) {
    	if ($ip == null) {
    		$ip = \Yii::$app->request->getUserIP();
    	}

        $this->cache->delete($ip);
    }

    private $expire = 8 * 3600;
}