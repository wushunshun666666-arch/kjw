<?php
/**
 * AccessRule.php
 *
 * @author suxh
 */

namespace mylibs\yii\jsonrpc\filters;

use yii\base\Component;
use yii\base\Action;
use yii\base\Controller;

class AccessRule extends Component
{
    public $allow;
    public $actions;
    public $controllers;
    public $signKey;
    public $ips;
    public $matchCallback;
    public $denyCallback;

    public function allows($action, $request) {
        if ($this->matchAction($action)
            && $this->matchIP($request->getUserIP())
            && $this->matchController($action->controller)
            && $this->matchCustom($action)
        	&& $this->matchSign($request->get('sign'))
        ) {
            return $this->allow ? true : false;
        } else {
            return null;
        }
    }

    /**
     * @param Action $action the action
     * @return boolean whether the rule applies to the action
     */
    protected function matchAction($action) {
        return empty($this->actions) || in_array($action->id, $this->actions, true);
    }

    /**
     * @param Controller $controller the controller
     * @return boolean whether the rule applies to the controller
     */
    protected function matchController($controller) {
        return empty($this->controllers) || in_array($controller->uniqueId, $this->controllers, true);
    }

    /**
     * @param string $ip the IP address
     * @return boolean whether the rule applies to the IP address
     */
    protected function matchIP($ip) {
        if (empty($this->ips)) {
            return true;
        }
        foreach ($this->ips as $rule) {
            if ($rule === '*' || $rule === $ip || (($pos = strpos($rule, '*')) !== false && !strncmp($ip, $rule, $pos))) {
                return true;
            }
        }

        return false;
    }

    protected function matchSign($sign) {
    	if (empty($this->signKey)) {
    		return true;
    	}

    	$contents = file_get_contents('php://input');
    	return (md5($contents.$this->signKey) == $sign);
    }

    /**
     * @param Action $action the action to be performed
     * @return boolean whether the rule should be applied
     */
    protected function matchCustom($action) {
        return empty($this->matchCallback) || call_user_func($this->matchCallback, $this, $action);
    }
}
