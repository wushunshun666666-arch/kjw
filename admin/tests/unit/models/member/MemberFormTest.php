<?php

/**
 * 后台会员表单单元测试。
 *
 * 验证用户名、密码强度和确认密码等输入规则。
 */

namespace app\tests\unit\models\member;

use app\models\member\AddForm;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;

/**
 * 后台会员表单测试用例。
 */
final class MemberFormTest extends TestCase
{
    /**
     * 创建测试使用的Yii控制台应用。
     */
    protected function setUp(): void
    {
        parent::setUp();

        new Application([
            'id' => 'member-form-test',
            'basePath' => dirname(__DIR__, 4),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);
    }

    /**
     * 注销测试应用并清理全局状态。
     */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /**
     * 验证非法用户名和弱密码无法通过校验。
     */
    public function testRejectsInvalidUsernameAndWeakPassword(): void
    {
        $form = new AddForm();
        $form->username = '1 invalid';
        $form->password = '12345678';
        $form->passwordConfirm = '12345678';
        $form->nickname = '测试会员';

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('username'));
        $this->assertTrue($form->hasErrors('password'));
    }

    /**
     * 验证两次密码不一致时返回字段错误。
     */
    public function testRejectsPasswordConfirmationMismatch(): void
    {
        $form = new AddForm();
        $form->username = '1 invalid';
        $form->password = 'Test@1234';
        $form->passwordConfirm = 'Other@1234';
        $form->nickname = '测试会员';

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('passwordConfirm'));
    }
}
