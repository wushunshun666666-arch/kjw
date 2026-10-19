<?php

/**
 * 后台访问日志过滤器。
 *
 * 记录路由和经过脱敏、截断的请求参数，禁止密码、Token、Cookie 等秘密进入日志。
 */

namespace mylibs\yii\filters;

use Yii;
use yii\base\Action;
use yii\base\ActionFilter;
use yii\helpers\Json;

/** 统一记录后台访问行为并过滤敏感请求数据。 */
class AccessLog extends ActionFilter
{
    /** @var string 访问日志类别 */
    public $category = 'app\access';

    /** @var array<int, string> 允许额外记录的请求头名称 */
    public $headers = [];

    /**
     * 在控制器动作执行前记录已脱敏的访问数据。
     */
    public function beforeAction($action): bool
    {
        $this->log($action);
        return true;
    }

    /**
     * 组装路由、请求头和请求参数并写入访问日志。
     */
    protected function log(Action $action): void
    {
        $controllerId = $action->controller->id;
        $actionId = $action->id;
        $httpHeaders = [];
        if ($this->headers !== []) {
            $headers = Yii::$app->request->getHeaders();
            foreach ($this->headers as $name) {
                $httpHeaders[$name] = $headers->get($name);
            }
        }

        $post = Yii::$app->request->post();
        $get = Yii::$app->request->get();
        $form = array_merge(
            $httpHeaders,
            $post,
            $get
        );
        if ($form !== []) {
            $this->filterFormData($form);
        }

        $formText = $form === [] ? '' : Json::encode($form);
        Yii::info("{$controllerId}/{$actionId} {$formText}", $this->category);
    }

    /**
     * 递归移除敏感字段，并限制普通字符串写入日志的最大长度。
     *
     * @param array<string|int, mixed> $form 待过滤的请求数据
     */
    protected function filterFormData(array &$form): void
    {
        foreach ($form as $key => &$value) {
            if (is_array($value)) {
                $this->filterFormData($value);
            } elseif ($this->isSensitiveField((string) $key)) {
                unset($form[$key]);
            } elseif (is_string($value) && strlen($value) > 2000) {
                $value = substr($value, 0, 2000) . '...[truncated]';
            }
        }
        unset($value);
    }

    /**
     * 判断请求字段是否属于不可记录的敏感信息。
     */
    private function isSensitiveField(string $key): bool
    {
        $normalizedKey = strtolower($key);
        foreach (['password', 'token', 'authorization', 'cookie', 'secret', 'private_key', 'csrf'] as $word) {
            if (str_contains($normalizedKey, $word)) {
                return true;
            }
        }

        return false;
    }
}
