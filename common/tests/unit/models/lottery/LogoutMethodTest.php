<?php
use PHPUnit\Framework\TestCase;
require_once dirname(__DIR__, 5) . '/admin/controllers/Controller.php';
require_once dirname(__DIR__, 5) . '/admin/controllers/SiteController.php';
final class LogoutMethodTest extends TestCase
{
    public function testLogoutRejectsGetAndAllowsPost(): void
    {
        $controller = new \app\controllers\SiteController('site', Yii::$app);
        $method = new ReflectionMethod($controller, 'verbFilter');
        $filter = new \yii\filters\VerbFilter(['actions' => $method->invoke($controller)]);
        $original = Yii::$app->request;
        $response = Yii::$app->response;
        Yii::$app->set('response', new \yii\web\Response());
        try {
            Yii::$app->set('request', new class extends \yii\web\Request {
                public string $method = 'GET';
                public function getMethod() { return $this->method; }
            });
            $action = new \yii\base\Action('logout', $controller);
            try { $filter->beforeAction(new \yii\base\ActionEvent($action)); self::fail('GET must be rejected'); }
            catch (\yii\web\MethodNotAllowedHttpException $exception) { self::assertSame(405, $exception->statusCode); }
            Yii::$app->request->method = 'POST';
            self::assertTrue($filter->beforeAction(new \yii\base\ActionEvent($action)));
        } finally { Yii::$app->set('request', $original); Yii::$app->set('response', $response); }
    }
}
