<?php
namespace app\models\site;

use common\models\FormModel;
use common\caching\LoginCache;

/**
 * Login form
 */
class LoginForm extends FormModel
{
    public $username;
    public $password;
    public $captcha;

    /**
     * @inheritdoc
     */
    public function rules() {
        $rules = [
            // username and password are both required
            [['username', 'password'], 'required'],
        ];

        if ($this->isNeedCaptcha()) {
        	$rules[] = [['captcha'], 'required'];
        	$rules[] = [['captcha'], '\yii\captcha\CaptchaValidator'];
        }

        return $rules;
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Model::attributeLabels()
     */
    public function attributeLabels() {
    	return [
			'username'=> '用户名',
			'password'=> '密码',
    		'captcha' => '验证码',
    	];
    }

    public function isNeedCaptcha() {
    	$cache = new LoginCache();
    	 return ($cache->get() >= 3);
    }
}
