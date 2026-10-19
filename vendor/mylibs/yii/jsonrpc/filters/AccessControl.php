<?php
/**
 * AccessControl.php
 *
 * @author suxh
 */

namespace mylibs\yii\jsonrpc\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\ForbiddenHttpException;

class AccessControl extends ActionFilter
{
    public $denyCallback;
    public $ruleConfig = ['class' => 'mylibs\yii\jsonrpc\filters\AccessRule'];
    public $rules = [];

    public function init() {
        parent::init();
        foreach ($this->rules as $i => $rule) {
            if (is_array($rule)) {
                $this->rules[$i] = Yii::createObject(array_merge($this->ruleConfig, $rule));
            }
        }
    }

    public function beforeAction($action) {
        $request = Yii::$app->getRequest();
        /* @var $rule AccessRule */
        foreach ($this->rules as $rule) {
            if ($allow = $rule->allows($action, $request)) {
                return true;
            } elseif ($allow === false) {
                if (isset($rule->denyCallback)) {
                    call_user_func($rule->denyCallback, $rule, $action);
                } elseif (isset($this->denyCallback)) {
                    call_user_func($this->denyCallback, $rule, $action);
                } else {
                    $this->denyAccess();
                }
                return false;
            }
        }
        if (isset($this->denyCallback)) {
            call_user_func($this->denyCallback, null, $action);
        } else {
            $this->denyAccess();
        }
        return false;
    }

    protected function denyAccess() {
        throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
    }
}
