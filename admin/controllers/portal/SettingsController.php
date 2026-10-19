<?php

namespace app\controllers\portal;

use app\models\portal\settings\SettingsForm;
use app\models\portal\settings\SettingsService;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/** 登录链接及广告设置，继承登录认证、模块权限、CSRF 和操作日志。 */
final class SettingsController extends \app\controllers\Controller
{
    /** 上传和校验只允许 POST，图片预览只允许 GET。 */
    protected function verbFilter(): array
    {
        return ['index' => ['GET', 'POST'], 'check' => ['POST'], 'upload' => ['POST'], 'image' => ['GET']];
    }

    /** 显示或保存独立设置表单。 */
    public function actionIndex()
    {
        $service = new SettingsService();
        if (\Yii::$app->request->isPost) {
            $form = new SettingsForm();
            if (!$form->loadPost() || !$form->validate()) { return $this->jsonError($form->getError() ?: '提交内容不完整。'); }
            if (!$service->save($form)) { return $this->jsonError($service->getError()); }
            return $this->jsonSuccess('配置已保存，刷新前台即可查看。', ['url' => \app\helpers\Url::to(['index'])]);
        }
        return $this->render('index', ['model' => $service->form()]);
    }

    /** 校验表单字段。 */
    public function actionCheck()
    {
        $form = new SettingsForm();
        $form->loadPost();
        return $this->renderValidate($form);
    }

    /** 使用现有上传控件的响应协议，禁止上传响应被当作 HTML 执行。 */
    public function actionUpload()
    {
        \Yii::$app->response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        \Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        $service = new SettingsService();
        $result = $service->upload(UploadedFile::getInstanceByName('upload'));
        return $result === false ? $this->jsonError($service->getError()) : $this->jsonSuccess($result);
    }

    /** 从 Web 根目录外发送经过授权的图片预览。 */
    public function actionImage(string $reference)
    {
        $image = (new SettingsService())->image($reference);
        if ($image === null) { throw new NotFoundHttpException('图片不存在。'); }
        \Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        \Yii::$app->response->headers->set('Cache-Control', 'private, no-store');
        return \Yii::$app->response->sendFile($image['path'], basename($image['path']), ['inline' => true, 'mimeType' => $image['mime']]);
    }
}
