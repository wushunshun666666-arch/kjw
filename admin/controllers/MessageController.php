<?php

namespace app\controllers;

use app\models\message\MessageService;
use app\models\message\ReadForm;
use app\models\message\SearchForm;

/** 后台站内消息页面及读取接口控制器。 */
final class MessageController extends Controller
{
    /** 返回站内消息接口的HTTP方法限制。 */
    protected function verbFilter(): array
    {
        return [
            'list' => ['POST'],
            'get-menu-list' => ['GET'],
            'get-new-nums' => ['GET'],
            'read' => ['POST'],
            'mark-all-read' => ['POST'],
        ];
    }

    /** 允许所有已登录后台用户访问自己的站内消息接口。 */
    protected function withoutAuthorization(): array
    {
        return ['index', 'list', 'get-menu-list', 'get-new-nums', 'read', 'mark-all-read'];
    }

    /** 渲染当前后台用户的站内消息列表页面。 */
    public function actionIndex(): string
    {
        $searchForm = new SearchForm();
        $service = new MessageService();
        return $this->render('index', [
            'searchForm' => $searchForm,
            'conditionGroup' => $service->getConditionGroup(),
        ]);
    }

    /** 返回当前后台用户的站内消息分页数据。 */
    public function actionList(): string
    {
        $searchForm = new SearchForm();
        if (!$searchForm->loadPost('') || !$searchForm->validate()) {
            return $this->jsonError($searchForm->getError());
        }
        $service = new MessageService();
        return $this->jsonTable('_view_list', $service->search($searchForm));
    }

    /** 返回顶部消息菜单的最近消息和未读数量。 */
    public function actionGetMenuList(): string
    {
        return $this->jsonSuccess((new MessageService())->getMenuList());
    }

    /** 返回当前后台用户的未读消息数量。 */
    public function actionGetNewNums(): string
    {
        return $this->jsonSuccess(['nums' => (new MessageService())->getNewNums()]);
    }

    /** 标记一条属于当前用户的站内消息为已读。 */
    public function actionRead(): string
    {
        $form = new ReadForm();
        if (!$form->loadPost('') || !$form->validate()) {
            return $this->jsonError($form->getError());
        }
        $service = new MessageService();
        $data = $service->read($form);
        if ($data === false) {
            return $this->jsonError($service->getError());
        }
        return $this->jsonSuccess($data);
    }

    /** 将当前后台用户的全部站内消息标记为已读。 */
    public function actionMarkAllRead(): string
    {
        (new MessageService())->markAllRead();
        return $this->jsonSuccess(['nums' => 0]);
    }
}
