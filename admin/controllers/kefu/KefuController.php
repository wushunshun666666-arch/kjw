<?php

/**
 * 后台在线客服控制器。
 *
 * 提供三栏客服工作台所需的会话、消息、领取、转交、结束及已读接口。
 */

namespace app\controllers\kefu;

use app\controllers\Controller;
use app\models\kefu\KefuService;
use app\models\kefu\SendForm;
use app\models\kefu\TransferForm;
use Yii;
use common\resource\KefuMediaResource;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

/** 后台在线客服工作台入口。 */
final class KefuController extends Controller
{
    /** 配置写接口仅允许POST请求。 */
    protected function verbFilter(): array
    {
        return [
            'initiate' => ['POST'],
            'claim' => ['POST'],
            'transfer' => ['POST'],
            'end' => ['POST'],
            'send' => ['POST'],
            'upload' => ['POST'],
            'mark-read' => ['POST'],
        ];
    }

    /** 显示客服三栏工作台。 */
    public function actionIndex()
    {
        $service = new KefuService();
        $initialMemberId = (int) Yii::$app->request->get('member_id', 0);
        $conversationItems = $service->getConversationItems();
        $quickReplies = $service->getQuickReplies();
        $availableUsers = $service->getAvailableUsers();
        return $this->render('index', [
            'memberTotal' => count($conversationItems),
            'workbenchConfig' => $service->getWorkbenchConfig(
                $conversationItems,
                $quickReplies,
                $availableUsers,
                $initialMemberId
            ),
        ]);
    }

    /** 返回当前会话、会员详情和一页聊天消息。 */
    public function actionConversation()
    {
        $id = $this->getId();
        $service = new KefuService();
        $conversation = $service->getConversationData($id);
        if ($conversation === false) {
            return $this->jsonError($service->getError());
        }
        return $this->jsonSuccess('', [
            'conversation' => $conversation,
            'messages' => $service->getMessages($id),
        ]);
    }

    /** 按前后游标或指定自然日返回聊天消息。 */
    public function actionMessages()
    {
        $beforeId = (int) Yii::$app->request->get('before_id', 0);
        $afterId = (int) Yii::$app->request->get('after_id', 0);
        $date = trim((string) Yii::$app->request->get('date', ''));
        $service = new KefuService();
        if ($beforeId > 0 && $afterId > 0) {
            return $this->jsonError('消息游标参数不能同时使用。');
        }
        if ($date !== '' && ($beforeId > 0 || $afterId > 0)) {
            return $this->jsonError('历史日期不能与消息游标同时使用。');
        }
        if ($date !== '') {
            $dateObject = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
                return $this->jsonError('历史记录日期格式错误。');
            }
            return $this->jsonSuccess('', [
                'items' => $service->getHistoryMessages($this->getId(), $date),
            ]);
        }
        if ($afterId > 0) {
            return $this->jsonSuccess('', [
                'items' => $service->getMessagesAfter($this->getId(), $afterId),
            ]);
        }
        return $this->jsonSuccess('', [
            'items' => $service->getMessages($this->getId(), $beforeId > 0 ? $beforeId : null),
        ]);
    }

    /** 从会员详情主动创建或打开客服会话。 */
    public function actionInitiate()
    {
        $memberId = (int) Yii::$app->request->post('member_id', 0);
        if ($memberId < 1) {
            throw new NotFoundHttpException();
        }
        $service = new KefuService();
        $conversation = $service->initiate($memberId);
        if ($conversation === false) {
            return $this->jsonError($service->getError());
        }
        return $this->jsonSuccess('已打开会员客服会话。', ['id' => $conversation->id]);
    }

    /** 当前客服领取会话。 */
    public function actionClaim()
    {
        return $this->runBooleanAction('领取成功。', fn (KefuService $service): bool =>
            $service->claim($this->getId()));
    }

    /** 当前客服或管理员转交会话。 */
    public function actionTransfer()
    {
        $form = new TransferForm();
        if (!$form->loadPost() || !$form->validate()) {
            return $this->jsonError($form->getError());
        }
        return $this->runBooleanAction('转交成功。', fn (KefuService $service): bool => $service->transfer(
            $this->getId(),
            (int) $form->targetUserId
        ));
    }

    /** 当前客服或管理员结束会话。 */
    public function actionEnd()
    {
        return $this->runBooleanAction('会话已结束。', fn (KefuService $service): bool =>
            $service->end($this->getId()));
    }

    /** 当前接待客服发送消息。 */
    public function actionSend()
    {
        $form = new SendForm();
        if (!$form->loadPost() || !$form->validate()) {
            return $this->jsonError($form->getError());
        }
        $service = new KefuService();
        $message = $service->send($this->getId(), $form);
        if ($message === false) {
            $error = $service->getError();
            return $this->jsonError(
                is_string($error) && $error !== '' ? $error : '消息发送失败，请先领取会话后重试。'
            );
        }
        return $this->jsonSuccess('发送成功。', ['messageId' => (int) $message->id]);
    }

    /** 安全上传图片、语音或视频并返回服务端媒体标识。 */
    public function actionUpload()
    {
        $messageType = (int) Yii::$app->request->post('message_type', 0);
        $file = UploadedFile::getInstanceByName('media');
        if ($file === null) {
            return $this->jsonError('请选择需要上传的媒体文件。');
        }
        $resource = new KefuMediaResource();
        $result = $resource->storeTemporary($file, $messageType);
        if ($result === false) {
            return $this->jsonError($resource->getError());
        }
        return $this->jsonSuccess('上传成功。', $result);
    }

    /** 通过受控接口读取Web根目录外的客服媒体。 */
    public function actionMedia()
    {
        $key = (string) Yii::$app->request->get('key', '');
        $resource = new KefuMediaResource();
        $path = $resource->findReferencedPath($key);
        if ($path === false) {
            throw new NotFoundHttpException();
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        // 禁止浏览器进行MIME嗅探，避免媒体内容被当作脚本或HTML解释。
        Yii::$app->response->headers->set('X-Content-Type-Options', 'nosniff');
        Yii::$app->response->headers->set('Content-Security-Policy', "default-src 'none'; media-src 'self'");
        return Yii::$app->response->sendFile($path, basename($path), [
            'mimeType' => is_string($mime) ? $mime : 'application/octet-stream',
            'inline' => true,
        ]);
    }

    /** 清零当前客服未读数。 */
    public function actionMarkRead()
    {
        $lastVisibleMessageId = (int) Yii::$app->request->post('last_message_id', 0);
        if ($lastVisibleMessageId < 1) {
            return $this->jsonError('缺少最后可见消息ID。');
        }
        return $this->runBooleanAction('已读状态已更新。', fn (KefuService $service): bool =>
            $service->markRead($this->getId(), $lastVisibleMessageId));
    }

    /** 读取并校验会话ID。 */
    private function getId(): int
    {
        $id = (int) Yii::$app->request->get('id', 0);
        if ($id < 1) {
            throw new NotFoundHttpException();
        }
        return $id;
    }

    /** 执行简单布尔型服务操作并统一输出JSON。 */
    private function runBooleanAction(string $successMessage, callable $action)
    {
        $service = new KefuService();
        if (!$action($service)) {
            return $this->jsonError($service->getError());
        }
        return $this->jsonSuccess($successMessage);
    }
}
