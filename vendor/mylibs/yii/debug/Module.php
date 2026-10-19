<?php
/** 
 * Transaction.php
 * @author suxh
 */

namespace mylibs\yii\debug;

use \Yii;
use yii\debug\LogTarget;

class Module extends \yii\debug\Module
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $this->logTarget = Yii::$app->getLog()->targets['debug'] = new LogTarget($this);

        // delay attaching event handler to the view component after it is fully configured
        /*$app->on(Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->getView()->on(View::EVENT_END_BODY, [$this, 'renderToolbar']);
        });*/

        $app->getUrlManager()->addRules([
            $this->id => $this->id,
            $this->id . '/<controller:[\w\-]+>/<action:[\w\-]+>' => $this->id . '/<controller>/<action>',
        ], false);
    }
}