<?php

namespace frontend\controllers;

use frontend\models\ReferencePortalService;

/** 为同源前端提供只读兼容协议，保留后台认证边界。 */
final class ReferenceController extends \yii\web\Controller
{
    /** 所有兼容接口只读。 */
    public function behaviors(): array
    {
        return ['verbs' => ['class' => \yii\filters\VerbFilter::class, 'actions' => ['*' => ['GET']]]];
    }

    /** 外观与公开跳转配置。 */
    public function actionWebsite()
    {
        \Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $this->asJson($this->success((new ReferencePortalService())->website()));
    }

    /** 最新开奖、历史与匹配的预测。 */
    public function actionOpen()
    {
        \Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $this->asJson($this->success((new ReferencePortalService())->open()));
    }

    /** 彩种切换后轮询本站数据库，隐藏彩种与非法参数均不可读取。 */
    public function actionGame()
    {
        $form = new \frontend\models\GameForm();
        $form->loadGet();
        if (!$form->validate()) { throw new \yii\web\BadRequestHttpException('彩种参数格式不正确。'); }
        \Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $this->asJson($this->success((new \frontend\models\GamePortalService())->open($form->game)));
    }

    /** 公告目录。 */
    public function actionNotices() { return $this->asJson($this->success((new ReferencePortalService())->notices())); }

    /** 公告正文。 */
    public function actionDetail(string $id) { return $this->asJson($this->success((new ReferencePortalService())->notice($id))); }

    /** 用本站配置的登录地址生成二维码。 */
    public function actionQrcode()
    {
        $image = (new \frontend\models\PortalQrcodeService())->png();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \Yii::$app->response->headers->set('Content-Type', 'image/png');
        \Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return $image;
    }

    /** 只输出被当前配置引用的图片；文件不会作为脚本执行。 */
    public function actionImage(string $reference)
    {
        $image = (new ReferencePortalService())->image($reference);
        if ($image === null) { throw new \yii\web\NotFoundHttpException('图片不存在。'); }
        \Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        \Yii::$app->response->headers->set('Cache-Control', 'no-store');
        return \Yii::$app->response->sendFile($image['path'], basename($image['path']), ['inline' => true, 'mimeType' => $image['mime']]);
    }

    /** 统一与参考站一致的公开响应结构。 */
    private function success(array $data): array { return ['code' => 200, 'msg' => 'success', 'data' => $data]; }
}
