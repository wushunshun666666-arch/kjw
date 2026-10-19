<?php

/**
 * 项目事务所有权包装器。
 *
 * 仅提交或回滚当前实例亲自开启的事务；嵌套调用复用外层事务。
 */

namespace mylibs\yii\db;

use Yii;
use yii\db\Connection;
use yii\db\Transaction as YiiTransaction;

/** 项目统一的数据库事务包装器。 */
class Transaction
{
    /** @var YiiTransaction|null 当前包装器亲自开启的事务 */
    private ?YiiTransaction $transaction = null;

    /** 根据当前数据库连接建立事务所有权。 */
    public function __construct(?Connection $db = null)
    {
        $connection = $db ?? Yii::$app->db;
        $currentTransaction = $connection->getTransaction();
        if ($currentTransaction === null || !$currentTransaction->getIsActive()) {
            $this->transaction = $connection->beginTransaction();
        }
    }

    /** 析构时回滚仍处于活动状态的自有事务。 */
    public function __destruct()
    {
        if ($this->transaction !== null && $this->transaction->getIsActive()) {
            $this->transaction->rollBack();
        }
    }

    /** 回滚当前包装器亲自开启的事务。 */
    public function rollback(): void
    {
        if ($this->transaction !== null && $this->transaction->getIsActive()) {
            $this->transaction->rollBack();
        }
    }

    /** 提交当前包装器亲自开启的事务。 */
    public function commit(): void
    {
        if ($this->transaction !== null && $this->transaction->getIsActive()) {
            $this->transaction->commit();
        }
    }
}
