<?php

namespace common\models\lottery;

use common\resource\PortalImageResource;
use mylibs\yii\db\Transaction;

/** 原子保存网站和广告配置，保留审计与并发保护。 */
final class SettingsModel extends \common\models\LogicModel
{
    /** 保存经过表单校验的配置；未保存的上传文件不会公开。 */
    public function save(array $attributes): bool
    {
        $transaction = new Transaction();
        try {
            $entity = Settings::current();
            if ((int) ($attributes['version'] ?? -1) !== (int) $entity->version) {
                $transaction->rollback();
                return $this->setError('配置已被其他管理员修改，请刷新后重试。');
            }
            $previous = $entity->images();
            $entity->setAttributes(array_intersect_key($attributes, array_flip(SettingsRules::FIELDS)));
            if (!$entity->validate()) { $transaction->rollback(); return $this->addErrors($entity->getErrors()); }
            $resource = new PortalImageResource();
            foreach ($entity->images() as $reference) {
                if (!$resource->canUse($reference, $previous)) {
                    $transaction->rollback();
                    return $this->setError('图片不存在或不属于当前管理员，请重新上传。');
                }
            }
            if (!$entity->save(false) || !Audit::record('settings', 1, 'modify')->save()) {
                $transaction->rollback();
                return $this->setError('配置保存失败，请重试。');
            }
            $transaction->commit();
            return true;
        } catch (\yii\db\StaleObjectException $error) {
            $transaction->rollback();
            return $this->setError('配置已被其他管理员修改，请刷新后重试。');
        } catch (\Throwable $error) {
            $transaction->rollback();
            throw $error;
        }
    }
}
