<?php

/**
 * 后台访问日志脱敏单元测试。
 *
 * 验证敏感输入不会被访问日志持久记录。
 */

namespace app\tests\unit\filters;

use mylibs\yii\filters\AccessLog;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/** 后台访问日志过滤规则测试用例。 */
final class AccessLogTest extends TestCase
{
    /** 验证嵌套密码、Token、Cookie 和密钥字段会被移除。 */
    public function testFiltersNestedSensitiveFields(): void
    {
        $filter = new AccessLog();
        $form = [
            'username' => 'tester',
            'SensitiveLoginForm' => ['username' => 'member001', 'password' => 'secret-password'],
            'headers' => ['Authorization' => 'Bearer value', 'Cookie' => 'SID=value'],
            'private_key_value' => 'secret-key',
            '_csrf' => 'csrf-value',
        ];

        $this->filter($filter, $form);

        $this->assertSame(['username' => 'tester', 'SensitiveLoginForm' => ['username' => 'member001'], 'headers' => []], $form);
    }

    /** 验证超长普通文本会在写入日志前截断。 */
    public function testTruncatesLongText(): void
    {
        $filter = new AccessLog();
        $form = ['content' => str_repeat('a', 2100)];

        $this->filter($filter, $form);

        $this->assertSame(2014, strlen($form['content']));
        $this->assertStringEndsWith('...[truncated]', $form['content']);
    }

    /**
     * 通过反射执行受保护的日志过滤方法。
     *
     * @param AccessLog $filter 访问日志过滤器
     * @param array<string|int, mixed> $form 待过滤数据
     */
    private function filter(AccessLog $filter, array &$form): void
    {
        $method = new ReflectionMethod(AccessLog::class, 'filterFormData');
        $arguments = [&$form];
        $method->invokeArgs($filter, $arguments);
    }
}
