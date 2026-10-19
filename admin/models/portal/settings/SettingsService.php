<?php

namespace app\models\portal\settings;

use common\models\lottery\Settings;
use common\models\lottery\SettingsModel;
use common\resource\PortalImageResource;
use yii\web\UploadedFile;

/** 后台配置查询、保存和图片上传用例。 */
final class SettingsService extends \app\models\Service
{
    /** 加载当前配置到输入表单。 */
    public function form(): SettingsForm
    {
        $form = new SettingsForm();
        $form->setAttributes(Settings::current()->attributes);
        return $form;
    }

    /** 保存领域配置并传递错误。 */
    public function save(SettingsForm $form): bool
    {
        $model = new SettingsModel();
        return $model->save($form->getSafeAttributes()) ?: $this->addErrors($model->getErrors());
    }

    /** 保存独立上传资源，返回后台预览地址。 */
    public function upload(?UploadedFile $file): array|false
    {
        $resource = new PortalImageResource();
        $result = $resource->upload($file);
        if ($result === false) { return $this->addErrors($resource->getErrors()); }
        return $result + ['url' => \app\helpers\Url::to(['/portal/settings/image', 'reference' => $result['reference']])];
    }

    /** 后台只能预览已保存或本人上传的图片。 */
    public function image(string $reference): ?array
    {
        $resource = new PortalImageResource();
        return $resource->canUse($reference, Settings::current()->images()) ? $resource->resolve($reference) : null;
    }
}
