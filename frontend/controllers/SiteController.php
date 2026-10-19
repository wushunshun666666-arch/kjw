<?php

namespace frontend\controllers;

use frontend\models\PortalService;
use frontend\models\HistoryForm;
use yii\web\BadRequestHttpException;

/** 只读开奖门户入口，所有数据查询在应用服务中。 */
final class SiteController extends \yii\web\Controller
{
    /** 公共页面仅支持 GET，拒绝意外写请求。 */
    public function behaviors(): array
    {
        return ['verbs' => ['class' => \yii\filters\VerbFilter::class, 'actions' => [
            'index' => ['GET'], 'history' => ['GET'], 'trend' => ['GET'],
            'about' => ['GET'], 'notice' => ['GET'], 'latest' => ['GET'],
        ]]];
    }

    /** 显示最新开奖首页。 */
    public function actionIndex()
    {
        $form = new \frontend\models\GameForm();
        $form->loadGet();
        if (!$form->validate()) { throw new BadRequestHttpException('彩种参数格式不正确。'); }
        $service = new \frontend\models\GamePortalService();
        $this->view->params['portalAppearance'] = (new \frontend\models\ReferencePortalService())->website();
        $this->view->params['portalGames'] = $service->games();
        $this->view->params['portalGame'] = $form->game;
        if ($form->game === 'jnd28') { return $this->render('index'); }
        $data = $service->open($form->game);
        $this->view->params['multiGame'] = true;
        return $this->render('game', ['data' => $data, 'appearance' => $this->view->params['portalAppearance']]);
    }

    /** 返回已入库的公开最新结果，不在访问页面时触发上游采集。 */
    public function actionLatest()
    {
        return $this->asJson((new PortalService())->home());
    }

    /** 显示历史开奖分页。 */
    public function actionHistory()
    {
        $this->historyForm();
        return $this->redirect(['site/index', '#' => '/?tab=result']);
    }

    /** 显示按真实历史计算的和值分布。 */
    public function actionTrend()
    {
        $this->historyForm();
        return $this->redirect(['site/index', '#' => '/?tab=trend']);
    }

    /** 显示数据来源和接入说明。 */
    public function actionAbout() { return $this->redirect(['site/index', '#' => '/rule']); }

    /** 展示已发布公告。 */
    public function actionNotice(int $id)
    {
        (new PortalService())->notice($id);
        return $this->redirect(['site/index', '#' => '/notice/local-' . $id]);
    }

    /** 使用框架错误处理，避免泄露内部异常。 */
    public function actions(): array { return ['error' => ['class' => \yii\web\ErrorAction::class]]; }

    /** 验证查询参数，不接受数组或无界分页。 */
    private function historyForm(): HistoryForm
    {
        $form = new HistoryForm();
        $form->loadGet();
        if (!$form->validate()) { throw new BadRequestHttpException('查询条件格式不正确。'); }
        return $form;
    }
}
