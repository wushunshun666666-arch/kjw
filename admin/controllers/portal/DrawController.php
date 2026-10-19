<?php

namespace app\controllers\portal;

use app\models\portal\draw\SearchForm;
use app\models\portal\draw\DrawService;
use common\models\lottery\DrawInput;
use app\helpers\Url;

/** 受现有登录与路由权限保护的开奖记录入口。 */
final class DrawController extends \app\controllers\Controller
{
    /** 限制查询和入库请求方法。 */
    protected function verbFilter(): array
    {
        return ['index' => ['GET'], 'list' => ['POST'], 'add' => ['GET', 'POST'], 'check-add' => ['POST'], 'collect' => ['POST']];
    }

    /** 使用固定来源采集指定彩种，保留权限、CSRF 与日志保护。 */
    public function actionCollect()
    {
        $form = new \app\models\portal\draw\CollectForm();
        $form->loadGet();
        if (!$form->validate()) { return $this->jsonError($form->getError()); }
        $service = new DrawService();
        $result = $service->collect($form->game);
        if ($result === false) { return $this->jsonError($service->getError()); }
        return $this->jsonSuccess('采集完成，新增 ' . $result['inserted'] . ' 期。', $result);
    }

    /** 显示开奖记录查询页。 */
    public function actionIndex()
    {
        $service = new DrawService();
        return $this->render('index', ['model' => new SearchForm(), 'gameNames' => $service->getGameNames(), 'sources' => $service->getCollectionSources()]);
    }

    /** 返回不可变开奖记录的表格行。 */
    public function actionList()
    {
        $form = new SearchForm();
        if (!$form->loadPost() || !$form->validate()) { return $this->jsonError($form->getError()); }
        return $this->jsonTable('_view_list', (new DrawService())->search($form));
    }

    /** 校验标准开奖数据。 */
    public function actionCheckAdd()
    {
        $form = new DrawInput();
        $form->loadPost();
        return $this->renderValidate($form);
    }

    /** 录入已核实的开奖记录，不提供编辑历史结果的入口。 */
    public function actionAdd()
    {
        $form = new DrawInput(['source' => '人工核实']);
        $service = new DrawService();
        if ($form->loadPost()) {
            if (!$form->validate()) { return $this->jsonError($form->getError()); }
            if (!$service->receive($form)) { return $this->jsonError($service->getError()); }
            return $this->jsonSuccess('开奖记录已接收。', ['url' => Url::to(['index'])]);
        }
        return $this->render('add', ['model' => $form, 'gameNames' => $service->getGameNames()]);
    }
}
