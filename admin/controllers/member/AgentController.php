<?php

/**
 * 后台代理管理控制器。
 *
 * 负责输出会员推广关系概览页面和卡片树按需加载的直属下级节点。
 */

namespace app\controllers\member;

use app\controllers\Controller;
use app\models\member\AgentService;
use app\models\member\TreeNodeForm;

/**
 * 代理推广关系后台入口。
 */
final class AgentController extends Controller
{
    /**
     * 配置代理树节点查询接口允许使用的HTTP请求方法。
     */
    protected function verbFilter(): array
    {
        return [
            'children' => ['GET'],
        ];
    }

    /**
     * 显示代理推广关系管理页面。
     */
    public function actionIndex(): string
    {
        $service = new AgentService();

        return $this->render('index', [
            'overview' => $service->getPromotionOverview(),
        ]);
    }

    /**
     * 返回指定节点的直属下级推广会员。
     *
     * 返回后台统一AjaxResult结构，前端卡片树仅消费经过服务层整理的展示数据。
     */
    public function actionChildren(): string
    {
        $form = new TreeNodeForm();
        if (!$form->loadGet() || !$form->validate()) {
            return $this->jsonError($form->getError());
        }

        $service = new AgentService();
        return $this->jsonSuccess(['items' => $service->getChildren($form->getParentId())]);
    }
}
