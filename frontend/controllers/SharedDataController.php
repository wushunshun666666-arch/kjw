<?php

namespace frontend\controllers;

use frontend\models\DrawHistoryForm;
use frontend\models\GameForm;
use frontend\models\SharedDataAuth;
use frontend\models\SharedDataService;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

/** 只使用请求头鉴权的中心开奖只读API，所有响应遵循版本化JSON协议。 */
final class SharedDataController extends Controller
{
    private ?SharedDataAuth $sharedAuth = null;
    private SharedDataService $sharedService;

    /** 在应用配置就绪后加载当前部署角色，避免容器自动构造空的共享配置。 */
    public function init(): void
    {
        parent::init();
        $this->sharedAuth ??= new SharedDataAuth();
        $this->sharedService = new SharedDataService();
    }

    /** 测试可显式注入鉴权存储，HTTP输入不能设置此控制器配置。 */
    public function setSharedAuth(SharedDataAuth $auth): void { $this->sharedAuth = $auth; }

    /** 接口只允许GET，不接受任何写操作或浏览器跨域预检。 */
    public function behaviors(): array
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => ['*' => ['GET']]]];
    }

    /** 先认证中心客户、检查请求方法和配额，再允许执行数据查询。 */
    public function beforeAction($action): bool
    {
        $headers = \Yii::$app->request->headers;
        $clientId = $this->sharedAuth->authenticate($headers->get('X-Lottery-Client'), $headers->get('Authorization'));
        if (!parent::beforeAction($action)) {
            return false;
        }
        $retryAfter = $this->sharedAuth->consumeQuota($clientId);
        if ($retryAfter > 0) {
            \Yii::$app->response->headers->set('Retry-After', (string) $retryAfter);
            throw new TooManyRequestsHttpException('请求过于频繁，请稍后重试。');
        }
        return true;
    }

    /** 将边界错误统一成无缓存JSON，避免普通站点错误页泄漏部署细节。 */
    public function runAction($id, $params = [])
    {
        $response = \Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Vary', 'Authorization, X-Lottery-Client');
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $exception) {
            if ($exception instanceof HttpException) {
                if ($exception->statusCode === 401) {
                    $response->headers->set('WWW-Authenticate', 'Bearer realm="lottery-data"');
                }
                return $this->error($exception->statusCode, $exception->getMessage());
            }
            \Yii::error(['type' => get_class($exception), 'file' => $exception->getFile(), 'line' => $exception->getLine()], __METHOD__);
            return $this->error(500, '共享服务暂时不可用。');
        }
    }

    /** 验证彩种输入并返回中心数据库最近开奖。 */
    public function actionLatest(): Response
    {
        $form = new GameForm();
        $form->loadGet();
        if (!$form->validate()) {
            throw new BadRequestHttpException('彩种参数格式不正确。');
        }
        return $this->success($this->sharedService->latest($form));
    }

    /** 验证有界分页参数并返回固定快照的历史开奖。 */
    public function actionHistory(): Response
    {
        $form = new DrawHistoryForm();
        $form->loadGet();
        if (!$form->validate()) {
            throw new BadRequestHttpException('历史分页参数格式不正确。');
        }
        return $this->success($this->sharedService->history($form));
    }

    /** 输出独立于前台兼容接口的版本化成功响应。 */
    private function success(array $data): Response
    {
        return $this->asJson(['version' => 1, 'code' => 200, 'msg' => 'success', 'data' => $data]);
    }

    /** 使用正确HTTP状态码，错误内容不包含请求凭据或配置。 */
    private function error(int $status, string $message): Response
    {
        \Yii::$app->response->statusCode = $status;
        return $this->asJson(['version' => 1, 'code' => $status, 'msg' => $message, 'data' => null]);
    }
}
