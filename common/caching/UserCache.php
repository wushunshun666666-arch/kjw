<?php
/**
 * 管理后台用户数据缓存
 *
 * @author chenzhf
 */

namespace common\caching;

use common\models\system\User;
use mylibs\yii\caching\AppCache;

class UserCache extends AppCache
{
    /**
     * 取得系统用户信息
     * @return array 返回系统用户信息
     */
    public function get() {
        $user = $this->cache->get($this->key);
        if (!$user) {
            $items = $this->getUsers();

            $user = [];
            foreach ($items as $item) {
                $id = $item['id'];
                $name = $item['name'];
                $username = $item['username'];
                $departmentName = $item['departmentName'];
                $user[$id] =  "{$username}-{$name}-{$departmentName}" ;
            }

            $this->cache->set($this->key, $user, $this->expire);
        }

        return $user;
    }

    public function delete() {
        $this->cache->delete($this->key);
    }

    private function getUsers() {
        $query = User::find();
        $query->select(['user.id', 'user.username', 'department.name AS departmentName', 'user.name',]);
        $query->join('INNER JOIN','department', 'department.id = user.department_id');
        $query->orderBy(['user.username'=>SORT_ASC]);
        $query->asArray();
        return $query->all();
    }

    private $expire = 24*3600;
    private $key = 'user-info';

}