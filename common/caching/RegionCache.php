<?php
/**
 * 区域数据缓存
 *
 * @author suxh
 */

namespace common\caching;

use common\models\common\Region;
use mylibs\yii\caching\AppCache;

class RegionCache extends AppCache
{
    /**
     * @return array 返回所有区域信息
     */
    public function getAllAsTree() {
        $data = $this->cache->get($this->keyTree);
        if (!$data) {
            $data = Region::getAllAsTree();

            $this->cache->set($this->keyTree, $data, $this->expire);
        }

        return $data;
    }

    public function deleteTree() {
        $this->cache->delete($this->keyTree);
    }

    private $expire = 24*3600;
    private $keyTree = 'region-tree1';

}