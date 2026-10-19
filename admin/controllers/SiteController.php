<?php
namespace app\controllers;

use Yii;
use app\models\site\LoginForm;
use app\models\site\ProfileForm;
use app\models\site\ModifyPasswordForm;
use app\models\site\SiteService;
use app\models\site\FeedbackForm;
use app\helpers\Url;

/**
 * Site controller
 */
class SiteController extends Controller
{
    protected function accessAllow() {
        // 登录页和错误页，无需登录也可以访问
        return ['login', 'captcha', 'error', 'upload-result'];
    }

    protected function withoutAuthorization() {
        return ['index', 'error', 'login', 'logout'];
    }

    /**
     * (non-PHPdoc)
     * @see \yii\base\Controller::actions()
     */
    public function actions() {
    	return array_merge(parent::actions(), [
    		'captcha' => [
    			'class' => 'yii\captcha\CaptchaAction',
    			'height' => 40,
    			'minLength' => 5,
    			'maxLength' => 5,
    		],
    	]);
    }

    public function actionIndex() {
        $this->layout = 'index';
        return $this->render('index');
    }

    public function actionLogin() {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->loadPost()) {
        	if (!$model->validate()) {
        		return $this->jsonError($model->getError());
        	}

        	$service = new SiteService();
        	$outOfLimit = false;
        	if (!$service->login($outOfLimit, $model->username, $model->password)) {
        		return $this->jsonError($service->getError(), ['outOfLimit' => $outOfLimit]);
        	}

        	$homeUrl = \Yii::$app->getHomeUrl();
            return $this->jsonSuccess(['url'=>$homeUrl]);
        } else {
    		$this->layout = 'login';
        	return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout() {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionProfile() {
        $model = new ProfileForm();
        $service = new SiteService();
        if ($model->loadPost()) {
            if (!$model->validate()) {
                return $this->jsonError($model->getError());
            }

            if (!$service->modifyProfile($model)) {
                return $this->jsonError($service->getError());
            }
            return $this->jsonSuccess('修改成功。');
        }
        else {
            $user = $service->getProfile();
            if (!$user) {
                return $this->renderError($service->getError());
            }
            $model->load($user->attributes, '');
            $model->department = $user->department->name;

            return $this->render('profile', [
                'model' => $model,
            ]);
        }
    }

    public function actionModifyPassword() {
        $model = new ModifyPasswordForm();
        $service = new SiteService();
        if ($model->loadPost()) {
            if (!$model->validate()) {
                return $this->jsonError($model->getError());
            }

            if (!$service->modifyPassword($model)) {
                return $this->jsonError($service->getError());
            }
            return $this->jsonSuccess('密码修改成功，请重新登录。', [
                'url' => Url::to(['site/login']),
            ]);
        }
        else {
            return $this->render('modify_password', [
                'model' => $model,
            ]);
        }
    }

    public function actionFeedback() {
        $model = new FeedbackForm();
        $service = new SiteService();
        if ($model->loadPost()) {
            if (!$model->validate()) {
                return $this->jsonError($model->getError());
            }
            if (!$service->feedback($model)) {
                return $this->jsonError($service->getError());
            }

            return $this->jsonSuccess('已经成功提交，感谢您的反馈！');
        }
        else {
            return $this->render('feedback', [
                'model' => $model,
            ]);
        }
    }

    public function actionUploadResult() {
    	$error = \Yii::$app->request->get('error');
    	if ($error) {
    		return $this->jsonError($error);
    	}
    	else {
    		$alias = \Yii::$app->request->get('alias');
    		$url = \Yii::$app->request->get('url');
    		if (!$alias || !$url) {
    			return $this->jsonError('返回格式错误');
    		}
    		return $this->jsonSuccess(['alias'=>$alias, 'url'=>$url]);
    	}
    }

    public function getMessageCenter() {
        $service = new SiteService();
        return $service->getMessageCenter();
    }
}
