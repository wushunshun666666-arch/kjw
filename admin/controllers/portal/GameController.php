<?php

namespace app\controllers\portal;

use app\controllers\Controller;
use app\models\portal\game\AddForm;
use app\models\portal\game\ModifyForm;
use app\models\portal\game\SearchForm;
use app\models\portal\game\GameService;
use yii\web\NotFoundHttpException;
use app\helpers\Url;

/** 继承现有认证、路由授权、CSRF 和操作日志的彩种管理入口。 */
final class GameController extends Controller
{
    /** 列表和字段校验仅接受 POST；保存页保留 GET 渲染。 */
    protected function verbFilter(): array
    {
        return ['index' => ['GET'], 'list' => ['POST'], 'add' => ['GET', 'POST'],
            'modify' => ['GET', 'POST'], 'check-add' => ['POST'], 'check-modify' => ['POST'], 'detail' => ['GET']];
    }

    /** 显示查询页面。 */
    public function actionIndex()
    {
        $service = new GameService();
        return $this->render('index', ['model' => new SearchForm(), 'enabledNames' => $service->getEnabledNames()]);
    }

    /** 返回标准异步表格行。 */
    public function actionList()
    {
        $form = new SearchForm();
        if (!$form->loadPost() || !$form->validate()) { return $this->jsonError($form->getError()); }
        return $this->jsonTable('_view_list', (new GameService())->search($form));
    }

    /** 校验新增字段。 */
    public function actionCheckAdd()
    {
        $form = new AddForm();
        $form->loadPost();
        return $this->renderValidate($form);
    }

    /** 校验修改字段。 */
    public function actionCheckModify()
    {
        $form = new ModifyForm();
        $form->loadPost();
        return $this->renderValidate($form);
    }

    /** 新增彩种。 */
    public function actionAdd()
    {
        $form = new AddForm();
        $service = new GameService();
        if ($form->loadPost()) {
            if (!$form->validate()) { return $this->jsonError($form->getError()); }
            if (!$service->add($form)) { return $this->jsonError($service->getError()); }
            return $this->jsonSuccess('保存成功。', ['url' => Url::to(['index'])]);
        }
        return $this->render('add', ['model' => $form, 'enabledNames' => $service->getEnabledNames()]);
    }

    /** 修改彩种，不存在的主键返回 404。 */
    public function actionModify(int $id)
    {
        $service = new GameService();
        $data = $service->getDetail($id);
        if ($data === null) { throw new NotFoundHttpException(); }
        $form = new ModifyForm();
        if ($form->loadPost()) {
            if (!$form->validate()) { return $this->jsonError($form->getError()); }
            if (!$service->modify($id, $form)) { return $this->jsonError($service->getError()); }
            return $this->jsonSuccess('保存成功。', ['url' => Url::to(['index'])]);
        }
        $form->setAttributes($data->attributes);
        return $this->render('modify', ['model' => $form, 'enabledNames' => $service->getEnabledNames()]);
    }

    /** 显示只读详情。 */
    public function actionDetail(int $id)
    {
        $data = (new GameService())->getDetail($id);
        if ($data === null) { throw new NotFoundHttpException(); }
        return $this->render('detail', ['data' => $data]);
    }
}
