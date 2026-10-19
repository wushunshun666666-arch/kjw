<?php

/**
 * 客服快捷回复后台控制器。
 *
 * 提供全平台公共快捷短语的列表、新增、修改、启用和停用功能。
 */

namespace app\controllers\kefu;

use app\controllers\Controller;
use app\helpers\Url;
use app\models\kefu\QuickReplyForm;
use app\models\kefu\QuickReplySearchForm;
use app\models\kefu\QuickReplyService;
use Yii;
use yii\web\NotFoundHttpException;

/** 客服快捷回复管理入口。 */
final class QuickReplyController extends Controller
{
    /** 配置列表和状态操作仅允许POST请求。 */
    protected function verbFilter(): array
    {
        return ['list' => ['POST'], 'enable' => ['POST'], 'disable' => ['POST']];
    }

    /** 显示快捷回复列表。 */
    public function actionIndex()
    {
        $service = new QuickReplyService();
        return $this->render('index', [
            'model' => new QuickReplySearchForm(),
            'statusNames' => $service->getStatusNames(),
        ]);
    }

    /** 返回快捷回复异步表格。 */
    public function actionList()
    {
        $form = new QuickReplySearchForm();
        if (!$form->loadPost() || !$form->validate()) {
            return $this->jsonError($form->getError());
        }
        $service = new QuickReplyService();
        return $this->jsonTable('_view_list', $service->search($form));
    }

    /** 显示新增页面或保存新增快捷回复。 */
    public function actionAdd()
    {
        $form = new QuickReplyForm();
        if (!$form->loadPost()) {
            $service = new QuickReplyService();
            return $this->render('add', [
                'model' => $form,
                'statusNames' => $service->getStatusNames(),
            ]);
        }
        if (!$form->validate()) {
            return $this->jsonError($form->getError());
        }
        $service = new QuickReplyService();
        if (!$service->add($form)) {
            return $this->jsonError($service->getError());
        }
        return $this->jsonSuccess('新增快捷回复成功。', ['url' => Url::to(['index'])]);
    }

    /** 显示修改页面或保存快捷回复变更。 */
    public function actionModify()
    {
        $id = $this->getId();
        $service = new QuickReplyService();
        $form = new QuickReplyForm();
        if ($form->loadPost()) {
            $form->id = $id;
            if (!$form->validate() || !$service->modify($id, $form)) {
                return $this->jsonError($form->hasErrors() ? $form->getError() : $service->getError());
            }
            return $this->jsonSuccess('修改快捷回复成功。', ['url' => Url::to(['index'])]);
        }
        $model = $service->getById($id);
        if ($model === false) {
            return $this->renderError($service->getError());
        }
        $form->load($model->attributes, '');
        return $this->render('modify', [
            'model' => $form,
            'statusNames' => $service->getStatusNames(),
        ]);
    }

    /** 启用快捷回复。 */
    public function actionEnable()
    {
        $service = new QuickReplyService();
        if (!$service->enable($this->getId())) {
            return $this->jsonError($service->getError());
        }

        return $this->jsonSuccess('启用成功。');
    }

    /** 停用快捷回复。 */
    public function actionDisable()
    {
        $service = new QuickReplyService();
        if (!$service->disable($this->getId())) {
            return $this->jsonError($service->getError());
        }

        return $this->jsonSuccess('停用成功。');
    }

    /** 从查询参数读取快捷回复ID。 */
    private function getId(): int
    {
        $id = (int) Yii::$app->request->get('id', 0);
        if ($id < 1) {
            throw new NotFoundHttpException();
        }
        return $id;
    }
}
